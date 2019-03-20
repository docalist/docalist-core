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

namespace Docalist\Xml;

use RuntimeException;

/**
 * Exception générée par XmlReader lorsqu'une erreur est détectée dans le fichier XML.
 *
 * @author Daniel Ménard <daniel.menard@laposte.net>
 */
class XmlParseException extends RuntimeException
{
}
