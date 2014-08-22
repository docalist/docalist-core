<?php
namespace Docalist\Tests;

use WP_UnitTestCase;

class ServicesTest extends WP_UnitTestCase {
    public function testDefault() {
        $this->assertInstanceOf('Docalist\Services', docalist('services'));
        $this->assertInstanceOf('Docalist\Autoloader', docalist('autoloader'));

        $this->assertSame(docalist('services'), docalist('services')->get('services'));
    }
    public function testNames() {
        $t = [
            'services',
            'autoloader',
            'settings-repository',
            'docalist-core-settings',
            'site-root',
            'views',
            'file-cache',
            'table-manager',
            'sequences',
            'lookup',
            'docalist-core',
        ] ;

        $this->assertSame($t, docalist('services')->names());
    }

    public function testState() {
        $t = [
            'services' => true,
            'autoloader' => true,
            'settings-repository' => false,
            'docalist-core-settings' => false,
            'site-root' => false,
            'views' => false,
            'file-cache' => false,
            'table-manager' => false,
            'sequences' => false,
            'lookup' => true,
            'docalist-core' => true,
        ] ;

        $this->assertSame($t, docalist('services')->state());
    }

    public function testHas() {
        $this->assertTrue(docalist('services')->has('services'));
        $this->assertTrue(docalist('services')->has('autoloader'));
        $this->assertTrue(docalist('services')->has('site-root'));
        $this->assertTrue(docalist('services')->has('views'));
        $this->assertTrue(docalist('services')->has('file-cache'));
        $this->assertTrue(docalist('services')->has('table-manager'));
        $this->assertTrue(docalist('services')->has('sequences'));

        $this->assertFalse(docalist('services')->has('inexistant'));
    }

    public function testIsLoaded() {
        $this->assertTrue(docalist('services')->isLoaded('services'));
        $this->assertTrue(docalist('services')->isLoaded('autoloader'));

        $this->assertFalse(docalist('services')->isLoaded('site-root'));
        $this->assertFalse(docalist('services')->isLoaded('views'));
        $this->assertFalse(docalist('services')->isLoaded('file-cache'));
        $this->assertFalse(docalist('services')->isLoaded('table-manager'));
        $this->assertFalse(docalist('services')->isLoaded('sequences'));

        $this->assertFalse(docalist('services')->isLoaded('inexistant'));

        docalist('views');
        $this->assertTrue(docalist('services')->isLoaded('views'));

        docalist('table-manager');
        $this->assertTrue(docalist('services')->isLoaded('table-manager'));
    }

    public function testGet() {
        $this->assertInstanceOf('Docalist\Services', docalist('services'));
        $this->assertInstanceOf('Docalist\Autoloader', docalist('autoloader'));
//      $this->assertInternalType('string' , docalist('site-root')); NA en CLI
        $this->assertInstanceOf('Docalist\Views', docalist('views'));
//      $this->assertInstanceOf('Docalist\Cache\FileCache', docalist('file-cache')); utilise site-root, NA en CLI
        $this->assertInstanceOf('Docalist\Table\TableManager', docalist('table-manager'));
        $this->assertInstanceOf('Docalist\Sequences', docalist('sequences'));
    }

    public function testAdd() {
        docalist('services')->add('service-test1', 'Hi!');
        docalist('services')->add('service-test2', function() {
            return 'Hi!';
        });

        $this->assertTrue(docalist('services')->has('service-test1'));
        $this->assertTrue(docalist('services')->has('service-test2'));

        $this->assertTrue(docalist('services')->isLoaded('service-test1'));
        $this->assertFalse(docalist('services')->isLoaded('service-test2'));

        $this->assertSame('Hi!', docalist('service-test2'));
        $this->assertTrue(docalist('services')->isLoaded('service-test2'));

        docalist('services')->add([
            'service-test3' => 'Hop',
            'service-test4' =>function() {
                return 'Hip';
            }
        ]);

        $this->assertTrue(docalist('services')->has('service-test3'));
        $this->assertTrue(docalist('services')->has('service-test4'));

        $this->assertTrue(docalist('services')->isLoaded('service-test3'));
        $this->assertFalse(docalist('services')->isLoaded('service-test4'));

        $this->assertSame('Hop', docalist('service-test3'));
        $this->assertSame('Hip', docalist('service-test4'));
        $this->assertTrue(docalist('services')->isLoaded('service-test4'));
    }

    /**
     * Service existe déjà
     *
     * @expectedException LogicException
     */
    public function testAddExistant() {
        docalist('services')->add('aaa', 'Hi!');
        docalist('services')->add('aaa', 'Hi!');
    }

    /**
     * Service non trouvé
     *
     * @expectedException LogicException
     */
    public function testNotFound() {
        docalist('bbb');
    }
}