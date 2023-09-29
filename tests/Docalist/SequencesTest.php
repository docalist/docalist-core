<?php
/**
 * This file is part of Docalist Core.
 *
 * Copyright (C) 2012-2019 Daniel Ménard
 *
 * For copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */
declare(strict_types=1);

namespace Docalist\Tests;

use WP_UnitTestCase;
use Docalist\Sequences;

/**
 *
 * @author Daniel Ménard <daniel.menard@laposte.net>
 */
class SequencesTest extends WP_UnitTestCase
{
    public function testGetSequenceName()
    {
        $sequences = new Sequences();
        $this->assertSame($sequences->getSequenceName('grp', 'seq'), 'grp_last_seq');
        $this->assertSame($sequences->getSequenceName('grp', ''), 'grp_last_');
    }

    /**
     * Groupe non alnum.
     *
     * @expectedException InvalidArgumentException
     * @expectedExceptionMessage Invalid sequence group
     */
    public function testBadGroupName()
    {
        $sequences = new Sequences();
        $sequences->getSequenceName('a,b', 'seq');
    }

    /**
     * Nom de séquence non alnum.
     *
     * @expectedException InvalidArgumentException
     * @expectedExceptionMessage Invalid sequence name
     */
    public function testBadSequenceName()
    {
        $sequences = new Sequences();
        $sequences->getSequenceName('group', 'a,b');
    }

    /**
     * Nom de séquence de plus de 64 caractères.
     *
     * @expectedException InvalidArgumentException
     * @expectedExceptionMessage Sequence name too long
     */
    public function testBadSequence()
    {
        $sequences = new Sequences();

        // total 64 moins 6 pour '_last_' reste 58
        $sequences->getSequenceName(str_repeat('a', 59), '');
    }

    public function testIncrement()
    {
        $sequences = new Sequences();

        // Garantit que la séquence n'existe pas
        delete_option('grp_last_seq');

        // retourne 1 initiallement
        $this->assertSame($sequences->increment('grp', 'seq'), 1);

        // puis 2
        $this->assertSame($sequences->increment('grp', 'seq'), 2);

        // puis 3, etc.
        $this->assertSame($sequences->increment('grp', 'seq'), 3);
    }

    public function testGet()
    {
        $sequences = new Sequences();

        // Garantit que la séquence n'existe pas
        delete_option('grp_last_seq');

        // retourne 0 si la sequence n'existe pas
        $this->assertSame($sequences->get('grp', 'seq'), 0);

        // Retourne la séquence sinon
        $this->assertSame($sequences->increment('grp', 'seq'), 1);
        $this->assertSame($sequences->get('grp', 'seq'), 1);
    }

    public function testSet()
    {
        $sequences = new Sequences();

        // Garantit que la séquence n'existe pas
        delete_option('grp_last_seq');

        $this->assertSame($sequences->set('grp', 'seq', 123), 123);
        $this->assertSame($sequences->get('grp', 'seq'), 123);

        $this->assertSame($sequences->set('grp', 'seq', 456), 456);
        $this->assertSame($sequences->get('grp', 'seq'), 456);
    }

    public function testClear()
    {
        $sequences = new Sequences();

        // Garantit que la séquence n'existe pas
        delete_option('grp_last_seq');

        // La séquence n'existe pas, retourne 0
        $this->assertSame($sequences->clear('grp', 'seq'), 0);

        // La séquence existe pas, retourne 1
        $this->assertSame($sequences->increment('grp', 'seq'), 1);
        $this->assertSame($sequences->clear('grp', 'seq'), 1);

        // Suppression d'un groupe de trois séquences
        delete_option('grp_last_seq1');
        delete_option('grp_last_seq2');
        delete_option('grp_last_seq3');

        $this->assertSame($sequences->increment('grp', 'seq1'), 1);
        $this->assertSame($sequences->increment('grp', 'seq2'), 1);
        $this->assertSame($sequences->increment('grp', 'seq3'), 1);
        $this->assertSame($sequences->clear('grp'), 3);
    }

    public function testSetIfGreater()
    {
        $sequences = new Sequences();

        // Garantit que la séquence n'existe pas
        delete_option('grp_last_seq');

        // 1 = la séquence n'existait pas encore
        $this->assertSame($sequences->setIfGreater('grp', 'seq', 123), 1);
        $this->assertSame($sequences->get('grp', 'seq'), 123);

        // 2 = la séquence a été modifiée
        $this->assertSame($sequences->setIfGreater('grp', 'seq', 456), 2);
        $this->assertSame($sequences->get('grp', 'seq'), 456);

        // 0 = la séquence est déjà supérieure
        $this->assertSame($sequences->setIfGreater('grp', 'seq', 15), 0);
        $this->assertSame($sequences->get('grp', 'seq'), 456);
    }
}
