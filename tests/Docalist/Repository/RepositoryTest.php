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

use Docalist\Test\DocalistTestCase;
use WP_UnitTestCase;

use Docalist\Repository\Repository;
use Docalist\Repository\Exception\EntityNotFoundException;
use Docalist\Tests\Repository\Fixtures\MemoryRepository;
use Docalist\Tests\Type\Fixtures\Client;
use Docalist\Repository\Exception\BadIdException;

/**
 *
 * @author Daniel Ménard <daniel.menard@laposte.net>
 */
class RepositoryTest extends DocalistTestCase
{
    /**
     * Provider : retourne un dépôt du type à tester et deux ID valides pour
     * ce dépôt.
     *
     * @return array<array<int, Repository|string>>
     */
    public static function repositoryProvider(): array
    {
        return [
            [ new MemoryRepository(), 'client1', 'client2']
        ];
    }

    /**
     * @dataProvider repositoryProvider
     */
    public function testSaveLoadDelete(Repository $repo, string $id1, string $id2): void
    {
        // Vérifie qu'un ID est alloué si besoin
        $client = new Client(['name' => 'client without id']);
        $repo->save($client);
        $this->assertNotNull($client->getID(), 'save() alloue un ID si besoin');

        /* Store */

        // Création d'une entité
        $client1 = new Client(['name' => "client with id $id1"]);
        $client1->setID($id1);
        $repo->save($client1);
        $this->assertSame($id1, $client1->getID(), "save() ne change pas l'ID existant (création)");

        // Création puis mise à jour d'une entité
        $client2 = new Client(['name' => "client with id $id2"]);
        $client2->setID($id2);
        $repo->save($client2);
        $client2->name->assign("updated client with id $id2");
        $repo->save($client2);
        $this->assertSame($id2, $client2->getID(), "save() ne change pas l'ID existant (maj)");

        /* Load */

        // Vérifie les données brutes
        $data = $repo->loadRaw($client1->getID());
        $this->assertSame($client1->getPhpValue(), $data);

        $data = $repo->loadRaw($client2->getID());
        $this->assertSame($client2->getPhpValue(), $data);

        // Entité
        // $client = $repo->load($client1->getID());
        // $this->assertSame($client->getPhpValue(), $client1->getPhpValue());

//         $client = $repo->load($client2->getID(), get_class($client2)); // $obj::static(), ça marche
//         $this->assertTrue($client->equals($client2));

        /* Remove */
        $repo->delete($client1->getID());
        $catched = false;
        try {
            $repo->load($client1->getID());
        } catch (EntityNotFoundException $e) {
            $catched = true;
        }
        $this->assertTrue($catched, 'remove() deletes an entity');
    }

    /**
     * @dataProvider repositoryProvider
     */
    public function testRemoveInexistant(Repository $repo, string $id1, string $id2): void
    {
        $this->expectException(EntityNotFoundException::class);
        $this->expectExceptionMessage('not found');

        $repo->load('inexistant-' . $id1);
    }

//     public function decodeErrorProvider() {
//         return [
//             ['notjson'  => 1   ], // ce n'est pas une chaine de caractères
//             ['badjson'  => '[' ], // json invalide, mal formatté
//             ['notarray' => '1' ], // json valide, mais ne représente pas un tableau
//         ];
//     }

//     /**
//      * @dataProvider decodeErrorProvider
//      * @depends testNew
//      *
//      * @expectedException Docalist\Repository\Exception\RepositoryException
//      */
//     public function testDecodeError($badjson, MemoryRepository $repo) {
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

//     public function testEncode() {
//         $this->assertSame(
//             '["a"]',
//             MemoryRepository::jsonEncode(['a'], false),
//             'Repository::JsonEncode() en mode normal'
//         );

//         $this->assertSame(
//             "[\n    \"a\"\n]",
//             MemoryRepository::jsonEncode(['a'], true),
//             'Repository::JsonEncode() en mode pretty'
//         );
//     }

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
