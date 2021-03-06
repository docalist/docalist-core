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

namespace Docalist\Tests\Repository;

use Docalist\Repository\SettingsRepository;

//use Docalist\Tests\Type\Fixtures\Client;

/**
 *
 * @author Daniel Ménard <daniel.menard@laposte.net>
 */
class SettingsRepositoryTest extends RepositoryTest
{
    public function repositoryProvider()
    {
        return [
            [ new SettingsRepository(), 'option1', 'option2']
        ];
    }

    /**
     * @depends testNew
     */
//     public function testSave(MemoryRepository $repo) {
//         $client = new Client(null, null, 'c1');

//         $repo->save($client);
//         $this->assertSame('{"name":"noname"}', $repo->data['c1']);

//         $client->name = 'daniel';
//         $repo->save($client);
//         $this->assertSame('{"name":"daniel"}', $repo->data['c1']);

//         return $repo;
//     }

    /**
     * @depends testStore
     */
//     public function testLoad(MemoryRepository $repo) {
//         $data = $repo->load('c1');
//         $this->assertSame(['name' => 'daniel'], $data);

//         $client = $repo->load('c1', Client::className());
//         $this->assertSame(['name' => 'daniel'], $client->value());
//         $this->assertSame('c1', $client->getID());

//         return $repo;
//     }

    /**
     * @depends testLoad
     */
//     public function testRemove(MemoryRepository $repo) {
//         $repo->delete('c1');
//         $this->assertFalse(isset($repo->data['c1']));
//     }

    public function badIdProvider()
    {
        return [
            [ null ],                   // null
            [ 4128 ],                   // un entier
            [ 1.2 ],                    // un float
            [ true ],                   // un bool
            [ [] ],                     // un tableau
            [ (object) [] ],            // un objet
            [ str_repeat('a', 55) ],    // une chaine de plus de 55/64 caractères
            [ 'AB' ],                   // une chaine avec des majus
            [ 'é' ],                    // une chaine avec des accents
            [ '%' ],                    // une chaine avec des caractères incorrects
            [ '-' ],                    // une chaine qui commence par un tiret
            [ 'a--b' ],                 // une chaine avec double tirets
            [ 'a-' ],                   // une chaine qui termine par un tiret
        ];
    }

    /**
     * @depends testNew
     * @expectedException Docalist\Repository\Exception\BadIdException
     */
//     public function testLoadInvalidId(MemoryRepository $repo) {
//         $repo->load(null);
//     }

//     public function decodeErrorProvider() {
//         return [
//             ['notjson'  => "new"   ], // ce n'est pas une chaine de caractères
//             ['badjson'  => '[' ], // json invalide, mal formatté
//             ['notarray' => '1' ], // json valide, mais ne représente pas un tableau
//         ];
//     }

    /**
     * @dataProvider decodeErrorProvider
     * @depends testNew
     *
     * @expectedException Docalist\Repository\Exception\RepositoryException
     */
//     public function testDecodeError($badjson, MemoryRepository $repo) {
//         echo 'testDecodeError ', $badjson;
//         die();
//         $repo->data['bad'] = $badjson;

//         $repo->load('bad');
//     }

//     /**
//      * @expectedException Docalist\Repository\Exception\RepositoryException
//      * @expectedExceptionMessage abc12
//      */
//      public function testJsonDecodeError() {
//          MemoryRepository::jsonDecode('[', 'abc12');
//      }

//     public function testDecode() {
//          $this->assertSame(['a'], MemoryRepository::jsonDecode('["a"]', 'abc12'));
//      }

//     /**
//      * @expectedException Docalist\Repository\Exception\RepositoryException
//      * @expectedExceptionMessage abc12
//      */
//      public function testJsonDecodeError() {
//          MemoryRepository::jsonDecode('[', 'abc12');
//      }
}
