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

namespace Docalist\Forms\Traits;

use Docalist\Forms\Comment;
use Docalist\Forms\Text;
use Docalist\Forms\HtmlBlock;
use Docalist\Forms\Tag;
use Docalist\Forms\Input;
use Docalist\Forms\Password;
use Docalist\Forms\Hidden;
use Docalist\Forms\Textarea;
use Docalist\Forms\Checkbox;
use Docalist\Forms\Radio;
use Docalist\Forms\Button;
use Docalist\Forms\Submit;
use Docalist\Forms\Reset;
use Docalist\Forms\Select;
use Docalist\Forms\Checklist;
use Docalist\Forms\Fieldset;
use Docalist\Forms\Table;
use Docalist\Forms\EntryPicker;
use Docalist\Forms\Container;
use Docalist\Forms\Div;
use Docalist\Forms\Radiolist;

/**
 * Ce trait contient des méthodes qui permettent de créer tous les types d'items.
 *
 * Il n'est utilisé que par Container.
 *
 * Les méthodes se contentent de faire add(new Item) mais cela simplifie la création des formulaires et cela permet
 * d'avoir de l'autocomplétion dans les IDE :
 *
 * - $container->add(new Input())->setRepeatable(true) : pas de completion sur setRepeatable car add() retourne
 *   un Item et un Item n'a pas cette méthode.
 *
 * - $container->input()->setRepeatable(true) : on a de l'autocompletion car la méthode input() retourne un Input.
 *
 * @author Daniel Ménard <daniel.menard@laposte.net>
 */
trait ItemFactoryTrait
{
    /**
     * Crée un commentaire et l'ajoute au container.
     *
     * @param string $content Le contenu du commentaire.
     *
     * @return Comment
     */
    public function comment(string $content = ''): Comment
    {
        return new Comment($content, $this);
    }

    /**
     * Crée un bloc de texte et l'ajoute au container.
     *
     * @param string $content Le contenu du bloc.
     *
     * @return Text
     */
    public function text(string $content = ''): Text
    {
        return new Text($content, $this);
    }

    /**
     * Crée un bloc html et l'ajoute au container.
     *
     * @param string $content Le code html du bloc.
     *
     * @return HtmlBlock
     */
    public function html(string $content = ''): HtmlBlock
    {
        return new HtmlBlock($content, $this);
    }

    /**
     * Crée un tag et l'ajoute au container.
     *
     * @param string    $tag        Optionnel, le tag de l'élément (div par défaut).
     * @param string    $content    Optionnel, le contenu de l'élément.
     * @param array     $attributes Optionnel, les attributs de l'élément.
     *
     * @return Tag
     */
    public function tag(string $tag = 'div', string $content = '', array $attributes = []): Tag
    {
        return new Tag($tag, $content, $attributes, $this);
    }

    /**
     * Crée un tag <p> et l'ajoute au container.
     *
     * @param string    $content    Optionnel, le contenu de l'élément.
     * @param array     $attributes Optionnel, les attributs de l'élément.
     *
     * @return Tag
     */
    public function p(string $content = '', array $attributes = []): Tag
    {
        return new Tag('p', $content, $attributes, $this);
    }

    /**
     * Crée un tag <span> et l'ajoute au container.
     *
     * @param string    $content    Optionnel, le contenu de l'élément.
     * @param array     $attributes Optionnel, les attributs de l'élément.
     *
     * @return Tag
     */
    public function span(string $content = '', array $attributes = []): Tag
    {
        return new Tag('span', $content, $attributes, $this);
    }

    /**
     * Crée un élément Input (type 'text') et l'ajoute au container.
     *
     * @param string    $name       Optionnel, le nom de l'élément.
     * @param array     $attributes Optionnel, les attributs de l'élément.
     *
     * @return Input
     */
    public function input(string $name = '', array $attributes = []): Input
    {
        return new Input($name, $attributes, $this);
    }

    /**
     * Crée un élément Password et l'ajoute au container.
     *
     * @param string    $name       Optionnel, le nom de l'élément.
     * @param array     $attributes Optionnel, les attributs de l'élément.
     *
     * @return Password
     */
    public function password(string $name = '', array $attributes = []): Password
    {
        return new Password($name, $attributes, $this);
    }

    /**
     * Crée un élément Hidden et l'ajoute au container.
     *
     * @param string    $name       Optionnel, le nom de l'élément.
     * @param array     $attributes Optionnel, les attributs de l'élément.
     *
     * @return Hidden
     */
    public function hidden(string $name = '', array $attributes = []): Hidden
    {
        return new Hidden($name, $attributes, $this);
    }

    /**
     * Crée un élément Textarea et l'ajoute au container.
     *
     * @param string    $name       Optionnel, le nom de l'élément.
     * @param array     $attributes Optionnel, les attributs de l'élément.
     *
     * @return Textarea
     */
    public function textarea(string $name = '', array $attributes = []): TextArea
    {
        return new Textarea($name, $attributes, $this);
    }

    /**
     * Crée un élément Checkbox et l'ajoute au container.
     *
     * @param string    $name       Optionnel, le nom de l'élément.
     * @param array     $attributes Optionnel, les attributs de l'élément.
     *
     * @return Checkbox
     */
    public function checkbox(string $name = '', array $attributes = []): Checkbox
    {
        return new Checkbox($name, $attributes, $this);
    }

    /**
     * Crée un élément Radio et l'ajoute au container.
     *
     * @param string    $name       Optionnel, le nom de l'élément.
     * @param array     $attributes Optionnel, les attributs de l'élément.
     *
     * @return Radio
     */
    public function radio(string $name = '', array $attributes = []): Radio
    {
        return new Radio($name, $attributes, $this);
    }

    /**
     * Crée un élément Button et l'ajoute au container.
     *
     * @param string    $label      Optionnel, le libellé du bouton.
     * @param string    $name       Optionnel, le nom du bouton.
     * @param array     $attributes Optionnel, les attributs du bouton.
     *
     * @return Button
     */
    public function button(string $label = '', string $name = '', array $attributes = []): Button
    {
        return new Button($label, $name, $attributes, $this);
    }

    /**
     * Crée un élément Submit et l'ajoute au container.
     *
     * @param string    $label      Optionnel, le libellé du bouton.
     * @param string    $name       Optionnel, le nom du bouton.
     * @param array     $attributes Optionnel, les attributs du bouton.
     *
     * @return Submit
     */
    public function submit(string $label = '', string $name = '', array $attributes = []): Submit
    {
        return new Submit($label, $name, $attributes, $this);
    }

    /**
     * Crée un élément Reset et l'ajoute au container.
     *
     * @param string    $label      Optionnel, le libellé du bouton.
     * @param string    $name       Optionnel, le nom du bouton.
     * @param array     $attributes Optionnel, les attributs du bouton.
     *
     * @return Reset
     */
    public function reset(string $label = '', string $name = '', array $attributes = []): Reset
    {
        return new Reset($label, $name, $attributes, $this);
    }

    /**
     * Crée un élément Select et l'ajoute au container.
     *
     * @param string    $name       Optionnel, le nom de l'élément.
     * @param array     $attributes Optionnel, les attributs de l'élément.
     *
     * @return Select
     */
    public function select(string $name = '', array $attributes = []): Select
    {
        return new Select($name, $attributes, $this);
    }

    /**
     * Crée un élément Checklist et l'ajoute au container.
     *
     * @param string    $name       Optionnel, le nom de l'élément.
     * @param array     $attributes Optionnel, les attributs de l'élément.
     *
     * @return Checklist
     */
    public function checklist(string $name = '', array $attributes = []): CheckList
    {
        return new Checklist($name, $attributes, $this);
    }

    /**
     * Crée un élément Radiolist et l'ajoute au container.
     *
     * @param string    $name       Optionnel, le nom de l'élément.
     * @param array     $attributes Optionnel, les attributs de l'élément.
     *
     * @return Radiolist
     */
    public function radiolist(string $name = '', array $attributes = []): Radiolist
    {
        return new Radiolist($name, $attributes, $this);
    }

    /**
     * Crée un élément Container et l'ajoute au container.
     *
     * @param string    $name       Optionnel, le nom de l'élément.
     * @param array     $attributes Optionnel, les attributs de l'élément.
     *
     * @return Container
     */
    public function container(string $name = '', array $attributes = []): Container
    {
        return new Container($name, $attributes, $this);
    }

    /**
     * Crée un élément EntryPicker et l'ajoute au container.
     *
     * @param string    $name       Le nom de l'élément.
     * @param array     $attributes Optionnel, les attributs de l'élément.
     *
     * @return EntryPicker
     */
    public function entryPicker(string $name = '', array $attributes = []): EntryPicker
    {
        return new EntryPicker($name, $attributes, $this);
    }

    /**
     * Crée un élément Table et l'ajoute au container.
     *
     * @param string    $name       Optionnel, le nom de l'élément.
     * @param array     $attributes Optionnel, les attributs de l'élément.
     *
     * @return Table
     */
    public function table(string $name = '', array $attributes = []): Table
    {
        return new Table($name, $attributes, $this);
    }

    /**
     * Crée un élément Fieldset et l'ajoute au container.
     *
     * @param string    $name       Optionnel, le nom de l'élément.
     * @param array     $attributes Optionnel, les attributs de l'élément.
     *
     * @return Fieldset
     */
    public function fieldset(string $name = '', array $attributes = []): FieldSet
    {
        return new Fieldset($name, $attributes, $this);
    }

    /**
     * Crée un élément Div et l'ajoute au container.
     *
     * @param string    $name       Optionnel, le nom de l'élément.
     * @param array     $attributes Optionnel, les attributs de l'élément.
     *
     * @return Div
     */
    public function div(string $name = '', array $attributes = []): Div
    {
        return new Div($name, $attributes, $this);
    }
}
