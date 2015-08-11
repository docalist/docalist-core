<?php
/**
 * This file is part of the "Docalist Core" plugin.
 *
 * Copyright (C) 2012-2014 Daniel Ménard
 *
 * For copyright and license information, please view the
 * LICENSE.txt file that was distributed with this source code.
 *
 * @package     Docalist
 * @subpackage  Tests
 * @author      Daniel Ménard <daniel.menard@laposte.net>
 */

namespace Docalist\Tests\Repository;

use WP_UnitTestCase;

use Docalist\Repository\Repository;
use Docalist\Repository\PostTypeRepository;
use Docalist\Type\Entity;
use Docalist\Tests\Type\Fixtures\Client;

class PostTypeRepositoryTest extends RepositoryTest {
    public function repositoryProvider() {
        return [
            [ new PostTypeRepository('myposttype'), 759, '237']
        ];
    }

    /**
     * @dataProvider repositoryProvider
     */
    public function testPostType(Repository $repo) {
        $this->assertSame('myposttype', $repo->postType());
    }

    /**
     * @depends testNew
     */
//     public function testSave(PostTypeRepository $repo) {
//         $client = new Client();

//         $repo->save($client);
//         $this->assertNotNull($client->id());
//         $this->assertSame('{"name":"noname"}', get_post_field('post_excerpt', $client->id()));

//         $client->name = 'daniel';
//         $repo->save($client);
//         $this->assertSame('{"name":"daniel"}', get_post_field('post_excerpt', $client->id()));

//         $repo->save(new Client(null, null, '546')); // un entier sous forme de chaine

//         return $repo;
//     }

    /**
     * @depends testNew
     */
//     public function testLoad(PostTypeRepository $repo) {
//         $client = new Client(['name' => 'daniel']);
//         $repo->save($client);

//         $client2 = $repo->load($client->id(), Client::className());
//         $this->assertTrue($client2->equals($client));
//     }

    /**
     * @depends testNew
     * @expectedException Docalist\Repository\Exception\EntityNotFoundException
     */
//     public function testLoadInexistant(PostTypeRepository $repo) {
//         $repo->load(123456789);
//     }

    public function badIdProvider() {
        return [
            [ null ],                   // null
            [ true ],                   // un bool
            [ [] ],                     // un tableau
            [ (object) [] ],            // un objet
            [ 'AB' ],                   // une chaine pas un nombre
            [ 1.2 ],                    // une chaine avec des accents
            [ 0 ],                      // int à zéro
            [ '0' ],                    // int à zéro
            [ -1 ],                     // int négatif
        ];
    }

    /**
     * @dataProvider badIdProvider
     * @depends testNew
     * @expectedException Docalist\Repository\Exception\BadIdException
     */
//     public function testLoadInvalidId($badId, PostTypeRepository $repo) {
//         $repo->load($badId);
//     }

    /**
     * @depends testNew
     */
//     public function testRemove(PostTypeRepository $repo) {
//         $client = new Client();

//         $repo->save($client);
//         $this->assertNotNull($client->id());
//         $this->assertSame('{"name":"noname"}', get_post_field('post_excerpt', $client->id()));

//         $repo->delete($client->id());
//         $this->assertSame('', get_post_field('post_excerpt', $client->id()));
//     }

    /**
     * @depends testNew
     * @expectedException Docalist\Repository\Exception\EntityNotFoundException
     */
//     public function testRemoveInexistant(PostTypeRepository $repo) {
//         $repo->delete(123456789);
//     }

    /**
     * @expectedException Docalist\Repository\Exception\BadIdException
     */
//     public function testStoreEntityWithourId() {
//         $repo = new DirectoryRepository($this->dir);
//         $repo->save(new Client());
//     }

}