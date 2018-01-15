<?php
/**
 * This file is part of Docalist Core.
 *
 * Copyright (C) 2012-2018 Daniel Ménard
 *
 * For copyright and license information, please view the
 * LICENSE.txt file that was distributed with this source code.
 */
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
    public function comment($content = null)
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
    public function text($content = null)
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
    public function html($content = null)
    {
        return new HtmlBlock($content, $this);
    }

    /**
     * Crée un tag et l'ajoute au container.
     *
     * @param string    $tag        Optionnel, le tag de l'élément (div par défaut).
     * @param string    $content    Optionnel, le contenu de l'élément.
     * @param array $   attributes  Optionnel, les attributs de l'élément.
     *
     * @return Tag
     */
    public function tag($tag = 'div', $content = null, array $attributes = null)
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
    public function p($content = null, array $attributes = null)
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
    public function span($content = null, array $attributes = null)
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
    public function input($name = null, array $attributes = null)
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
    public function password($name = null, array $attributes = null)
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
    public function hidden($name = null, array $attributes = null)
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
    public function textarea($name = null, array $attributes = null)
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
    public function checkbox($name = null, array $attributes = null)
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
    public function radio($name = null, array $attributes = null)
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
    public function button($label = null, $name = null, array $attributes = null)
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
    public function submit($label = null, $name = null, array $attributes = null)
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
    public function reset($label = null, $name = null, array $attributes = null)
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
    public function select($name = null, array $attributes = null)
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
    public function checklist($name = null, array $attributes = null)
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
    public function radiolist($name = null, array $attributes = null)
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
    public function container($name = null, array $attributes = null)
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
    public function entryPicker($name = null, array $attributes = null)
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
    public function table($name = null, array $attributes = null)
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
    public function fieldset($name = null, array $attributes = null)
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
    public function div($name = null, array $attributes = null)
    {
        return new Div($name, $attributes, $this);
    }
}
