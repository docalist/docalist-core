<?php
/**
 * This file is part of Docalist Core.
 *
 * Copyright (C) 2012-2019 Daniel Ménard
 *
 * For copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */
namespace Docalist\Tests\Repository;

use WP_UnitTestCase;

use Docalist\Repository\DirectoryRepository;
use Docalist\Tests\Type\Fixtures\Client;

/**
 *
 * @author Daniel Ménard <daniel.menard@laposte.net>
 */
class DirectoryRepositoryTest extends WP_UnitTestCase
{
    protected $dir;
    protected function rmTree($directory)
    {
        $files = array_diff(scandir($directory), array('.','..'));
        foreach ($files as $file) {
            $path = $directory . DIRECTORY_SEPARATOR . $file;
            if (is_dir($path)) {
                if (! $this->rmTree($path)) {
                    return false;
                }
            } else {
                if (! unlink($path)) {
                    return false;
                }
            }
        }

        return @rmdir($directory);
    }

    // Garantie que le répertoire utilisé pour les tests n'existe pas
    public function setUp()
    {
        $dir = sys_get_temp_dir() . '/DirectoryTest';
        if (file_exists($dir)) {
            if (is_dir($dir)) {
                if (! $this->rmTree($dir)) {
                    die('impossible de supprimer le rép temp');
                }
            } else {
                unlink($dir);
            }
        }

        $this->dir = $dir;
    }


    public function testNew()
    {
        $repo = new DirectoryRepository($this->dir);
        $this->assertTrue(is_dir($this->dir), 'DirectoryRepository::__construct() creates the directory');

        $this->assertSame(
            realpath($this->dir) . DIRECTORY_SEPARATOR,
            $repo->directory(),
            'DirectoryRepository::directory() returns the directory with a slash at the end'
        );
    }

    /**
     * @expectedException Docalist\Repository\Exception\RepositoryException
     * @expectedExceptionMessage Unable to create
     */
    public function testUnableToCreateDir()
    {
        touch($this->dir);
        new DirectoryRepository($this->dir);
        // on passe un fichier qui existe, donc il ne peut pas créer le dir
        // TODO : comment tester !is_writable(dir) ?
    }

//     /**
//      * @expectedException Docalist\Repository\Exception\RepositoryException
//      * @expectedExceptionMessage not writable
//      */
//     public function testWriteProtectedDir() {
//         $this->markTestSkipped('unable to test is_writable');
//     }

    public function testSave()
    {
        $repo = new DirectoryRepository($this->dir);

        // teste avec une entité qui a déjà un ID
        $client = new Client(null, null, 'abc12');
        $repo->save($client);
        $path = $repo->path($client->getID());
        $this->assertFileExists($path);
        $this->assertSame('{"name":"noname"}', file_get_contents($path));

        // teste avec une entité qui n'a pas d'ID
        $client = new Client();
        $repo->save($client);
        $this->assertNotNull($client->getID());
        $path = $repo->path($client->getID());
        $this->assertFileExists($path);
        $this->assertSame('{"name":"noname"}', file_get_contents($path));

        // teste avec une entité qui existe déjà
        $client->name = 'daniel';
        $repo->save($client);
        $this->assertSame('{"name":"daniel"}', file_get_contents($path));
    }

    /**
     * @expectedException Docalist\Repository\Exception\RepositoryException
     * @expectedExceptionMessage failed to open
     */
    public function testStoreError()
    {
        $repo = new DirectoryRepository($this->dir);
        $client = new Client(null, null, 'storeerror');

        $path = $repo->path($client->getID());
        mkdir($path, 0777, true);
        $repo->save($client);
    }

    public function testLoad()
    {
        $repo = new DirectoryRepository($this->dir);

        // test avec une entité qui a déjà un ID
        $client = new Client(['name' => 'daniel']);
        $repo->save($client);
        $client2 = $repo->load($client->getID());
        $this->assertTrue($client2->equals($client));
    }

    /**
     * @expectedException Docalist\Repository\Exception\EntityNotFoundException
     */
    public function testLoadInexistant()
    {
        $repo = new DirectoryRepository($this->dir);
        $repo->load('inexistant');
    }

    /**
     * @expectedException Docalist\Repository\Exception\RepositoryException
     * @expectedExceptionMessage failed to open
     */
    public function testLoadError()
    {
        $repo = new DirectoryRepository($this->dir);

        $path = $repo->path('loaderror');
        mkdir($path, 0777, true);
        $repo->load('loaderror');
    }

    public function badIdProvider()
    {
        return [
            [ null ],                   // null
            [ 4128 ],                   // un entier
            [ true ],                   // un bool
            [ [] ],                     // un tableau
            [ (object) [] ],            // un objet
            [ str_repeat('a', 65) ],    // une chaine de plus de 64 caractères
            [ 'AB' ],                   // une chaine avec des majus
            [ 'é' ],                    // une chaine avec des accents
            [ '%' ],                    // une chaine avec des caractères incorrects
            [ '-' ],                    // une chaine qui commence par un tiret
            [ 'a--b' ],                 // une chaine avec double tirets
            [ 'a-' ],                   // une chaine qui termine par un tiret
        ];
    }

    /**
     * @dataProvider badIdProvider
     * @expectedException Docalist\Repository\Exception\BadIdException
     */
    public function testLoadInvalidId($badId)
    {
        $repo = new DirectoryRepository($this->dir);
        $repo->load($badId);
    }

    public function testRemove()
    {
        $repo = new DirectoryRepository($this->dir);

        $client = new Client(null, null, 'zz14');
        $repo->save($client);
        $path = $repo->path($client->getID());
        $this->assertFileExists($path);

        $repo->delete('zz14');
        $this->assertFileNotExists($path);
    }

    /**
     * @expectedException Docalist\Repository\Exception\EntityNotFoundException
     */
    public function testRemoveInexistant()
    {
        $repo = new DirectoryRepository($this->dir);
        $repo->delete('inexistant');
    }

    /**
     * @expectedException Docalist\Repository\Exception\RepositoryException
     */
    public function testRemoveError()
    {
        $repo = new DirectoryRepository($this->dir);

        $client = new Client();
        $repo->save($client);
        $path = $repo->path($client->getID());

        unlink($path);
        mkdir($path);

        $repo->delete($client->getID());
    }
}
