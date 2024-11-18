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

namespace Docalist\Core;

use Docalist\AdminNotices;
use Docalist\Autoloader;
use Docalist\Cache\FileCache;
use Docalist\Cache\ObjectCache;
use Docalist\Container\ContainerBuilderInterface;
use Docalist\Container\ContainerInterface;
use Docalist\Forms\Choice;
use Docalist\Html;
use Docalist\Kernel\KernelExtension;
use Docalist\LogManager;
use Docalist\Lookup\LookupManager;
use Docalist\Lookup\TableLookup;
use Docalist\Lookup\ThesaurusLookup;
use Docalist\Repository\SettingsRepository;
use Docalist\Sequences;
use Docalist\Table\TableManager;
use Docalist\Tools\ToolsList;
use Docalist\Tools\ToolsPage;
use Docalist\Views;
use WP_Rewrite;
use WP_Roles;
use wpdb; // todo: bad

final class DocalistCoreExtension extends KernelExtension
{
    public function build(ContainerBuilderInterface $containerBuilder): void
    {
        $containerBuilder

        ->onClassLoad(Choice::class, static function ($container, $class) {
            Choice::setTableManager($container->get(TableManager::class));
        })

        // //////////////////////////////////// CONFIGURATION //////////////////////////////////////

        // Ajoute nos vues au service "views"
        ->listen(Views::class, static function (Views $views): void {
            $views->addDirectory('docalist-core', dirname(__DIR__, 2).'/views');
            $views->addDirectory('docalist-tools', dirname(__DIR__).'/Tools/views');
        })

        // ////////////////////////////////////// PARAMETRES ///////////////////////////////////////

        // root-dir (/web)
        ->set('root-dir', static function (): string {
            return dirname(__DIR__, 5);
        })

        // cache-dir (/docalist-cache)
        ->set('cache-dir', static function (ContainerInterface $container): string {
            return $container->string('root-dir').'/docalist-cache';
        })

        // public-dir (/web)
        ->set('public-dir', static function (ContainerInterface $container): string {
            return $container->string('root-dir').'/web';
        })

        // data-dir (/web/uploads/docalist-data)
        ->set('data-dir', static function (ContainerInterface $container): string {
            return $container->string('public-dir').'/uploads/docalist-data';
        })

        // tables-dir (/web/uploads/docalist-data/tables)
        ->set('tables-dir', static function (ContainerInterface $container): string {
            return $container->string('data-dir').'/tables';
        })

        // log-dir (/web/uploads/docalist-data/log)
        ->set('log-dir', static function (ContainerInterface $container): string {
            return $container->string('data-dir').'/log';
        })

        // config-dir (/web/uploads/docalist-data/config)
        ->set('config-dir', static function (ContainerInterface $container): string {
            return $container->string('data-dir').'/config';
        })

        // /////////////////////////////////////// SERVICES ////////////////////////////////////////

        // Autoloader
        ->set(Autoloader::class, static function (): Autoloader {
            $autoloader = new Autoloader();
            $autoloader->add('Docalist', __DIR__.'/class');
            $autoloader->add('Docalist\Tests', __DIR__.'/tests/Docalist');

            return $autoloader;
        })
        ->deprecate('autoloader', Autoloader::class, '2023-11-27')

        // Variable globales de wordpress
        // On les déclare comme services pour éviter d'avoir des "global $xxx" dans le code
        // et pour avoir la possibilité de créer des mocks dans les tests unitaires.
        ->set(wpdb::class, static fn () => $GLOBALS['wpdb'])
        ->alias('wordpress-database', wpdb::class) // todo: deprecate

        ->set(WP_Roles::class, static fn () => $GLOBALS['wp_roles'])
        ->alias('wordpress-roles', WP_Roles::class)

        ->set(WP_Rewrite::class, static fn () => $GLOBALS['wp_rewrite'])
        ->alias('wordpress-rewrite', WP_Rewrite::class)

        // Générateur de code html
        ->set(Html::class, static fn () => new Html('html5'))
        ->deprecate('html', Html::class, '2023-11-27')

        // Gestion des Settings
        ->set(SettingsRepository::class)
        ->deprecate('settings-repository', SettingsRepository::class, '2023-11-27')

        // Gestion des vues
        ->set(Views::class, static fn () => new Views([
            '' => get_stylesheet_directory(), // todo: à transférer dans un listener côté wordpress
        ]))
        ->deprecate('views', Views::class, '2023-11-27')

        // Gestion des logs
        ->set(LogManager::class)
        ->deprecate('logs', LogManager::class, '2023-11-27')

        // Gestion du cache
        ->set(FileCache::class, ['root-dir', 'cache-dir'])
        ->deprecate('file-cache', FileCache::class, '2023-11-27')

        // Cache des schémas
        ->set(ObjectCache::class, static fn () => new ObjectCache(DOCALIST_USE_WP_CACHE))
        ->deprecate('cache', ObjectCache::class, '2023-11-27')

        // Gestion des tables
        ->set(TableManager::class, static fn (ContainerInterface $container) => new TableManager(
            $container->string('tables-dir'),
            $container->get(FileCache::class),
            $container->get(LogManager::class)->get('logs')
        ))
        ->deprecate('table-manager', TableManager::class, '2023-11-27')

        // Gestion des séquences
        ->set(Sequences::class, [wpdb::class])
        ->deprecate('sequences', wpdb::class, '2023-11-27')

        // Gestion des lookups
        ->set(LookupManager::class)
        ->deprecate('lookup', LookupManager::class, '2023-11-27')

        ->set(TableLookup::class, [TableManager::class])
        ->deprecate('table-lookup', TableLookup::class, '2023-11-27')
        ->listen(LookupManager::class, static function (LookupManager $lookupManager, ContainerInterface $container): void {
            $lookupManager->setLookupService('table', $container->get(TableLookup::class));
        })

        ->set(ThesaurusLookup::class, [TableManager::class])
        ->deprecate('thesaurus-lookup', ThesaurusLookup::class, '2023-11-27')
        ->listen(LookupManager::class, static function (LookupManager $lookupManager, ContainerInterface $container): void {
            $lookupManager->setLookupService('thesaurus', $container->get(ThesaurusLookup::class));
        })

        // Admin Notices
        ->set(AdminNotices::class)
        ->deprecate('admin-notices', AdminNotices::class, '2023-11-27')

        // Plugin (ContainerAware)
        ->set(DocalistCorePlugin::class, static fn (ContainerInterface $container) => new DocalistCorePlugin(
            $container
        ))
        ->deprecate('docalist-core', DocalistCorePlugin::class, '2023-12-04')

        // Tools
        ->set(ToolsList::class, static fn () => new ToolsList(
            // C'est le filtre 'docalist-tools' qui fournit la liste des outils disponibles
            // todo: à changer, utiliser ContainerInterface::listen(Tools::class)
            static fn (): array => apply_filters('docalist-tools', [])
        ))

        ->set(ToolsPage::class, [ToolsList::class, Views::class])

        ;
    }
}
