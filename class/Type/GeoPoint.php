<?php
/**
 * This file is part of Docalist Core.
 *
 * Copyright (C) 2012-2018 Daniel Ménard
 *
 * For copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */
namespace Docalist\Type;

use Docalist\Type\Any;
use Docalist\Forms\Div;
use InvalidArgumentException;
use Docalist\Forms\Table;

/**
 * Un point géographique sur la terre, défini par sa latitude et sa longitude.
 *
 * La latitude et la longitude sont exprimés en degrés décimaux WGS84. Par exemple "48.1114428,-1.6810943" pour
 * Rennes, ce qui équivaut à 48°06'41"N,1°40'48"W en degrés sexagésimaux (degrés, minutes et secondes d'arcs).
 *
 * En interne, les coordonnées sont toujours stockées sous la forme d'un tableau associatif de la forme
 * ['lat' => y, 'lon' => x], mais le constructeur et la méthode assign() acceptent d'autres formats d'entrée :
 *
 * <code>
 * $rennes = new GeoPoint('48.1114428,-1.6810943');
 * $rennes = new GeoPoint(['lat' => 48.1114428, 'lon' => -1.6810943]);
 * $rennes = new GeoPoint([-1.6810943,48.1114428]); // ordre inverse, cf. GeoJSON.
 * </code>
 *
 * @author Daniel Ménard <daniel.menard@laposte.net>
 */
class GeoPoint extends Any
{
    /*
     * Ressources utiles :
     * - https://github.com/wimagguc/jquery-latitude-longitude-picker-gmaps
     * - https://developers.google.com/maps/documentation/javascript/reference#LatLng
     * - https://www.elastic.co/guide/en/elasticsearch/reference/current/geo-point.html
     * - https://github.com/treffynnon/Geographic-Calculations-in-PHP/blob/master/geography.class.php
     * - https://github.com/mjaschen/phpgeo/blob/master/src/Location/Coordinate.php
     * - https://github.com/thephpleague/geotools/blob/master/src/Coordinate/Coordinate.php
     */

    /*
     * A voir : on devrait plutôt hériter de la classe Composite et déclarer explicitement les champs lat/lon
     */

    public static function loadSchema(): array
    {
        return [
            'label' => __('Coordonnées', 'docalist-core'),
            'description' => __('Latitude et longitude en degrés décimaux.', 'docalist-core'),
        ];
    }

    /**
     * Définit la latitude du point.
     *
     * @param float $latitude La latitude, en degrés décimaux, comprise entre -90.0 et +90.0.
     *
     * @return self $this
     *
     * @throws InvalidArgumentException Si la latitude passée en paramètre est incorrecte.
     */
    public function setLatitude($latitude)
    {
        if (is_numeric($latitude)) {
            $latitude = floatval($latitude);
            if ($latitude >= -90.0 && $latitude <= 90.0) {
                $this->phpValue['lat'] = $latitude;

                return $this;
            }
        }

        throw new InvalidArgumentException('Invalid latitude, expecting float between -90 and +90');
    }

    /**
     * Retourne la latitude du point.
     *
     * @return float|null
     */
    public function getLatitude()
    {
        return isset($this->phpValue['lat']) ? $this->phpValue['lat'] : null;
    }

    /**
     * Définit la longitude du point.
     *
     * @param float $longitude La longitude, en degrés décimaux, comprise entre -180.0 et +180.0.
     *
     * @return self $this
     *
     * @throws InvalidArgumentException Si la longitude passée en paramètre est incorrecte.
     */
    public function setLongitude($longitude)
    {
        if (is_numeric($longitude)) {
            $longitude = floatval($longitude);
            if ($longitude >= -180.0 && $longitude <= 180.0) {
                $this->phpValue['lon'] = $longitude;

                return $this;
            }
        }

        throw new InvalidArgumentException('Invalid longitude, expecting float between -180 and +180');
    }

    /**
     * Retourne la longitude du point.
     *
     * @return float|null
     */
    public function getLongitude()
    {
        return isset($this->phpValue['lon']) ? $this->phpValue['lon'] : null;
    }

    /**
     * Initialise la valeur du point géographique.
     *
     * @param string|array $value Les coordonnées du point peuvent être indiquées :
     * - sous la forme d'une chaine de caractères de la forme : "latitude,longitude"
     * - sous la forme d'un tableau associatif de la forme : ['lat' => latitude, 'lon' => longitude]
     * - sous la forme d'un tableau numérique de la forme : [0 => longitude, 1 => latitude].
     *
     * Remarque : attention, dans le cas d'un tableau numérique, l'ordre usuel est inversé (longitude,latitude)
     * afin de respecter le standard GeoJSON (http://geojson.org/).
     *
     * @throws InvalidArgumentException Si les coordonnées sont incorrectes.
     *
     * @return self $this
     */
    public function assign($value): void
    {
        ($value instanceof Any) && $value = $value->getPhpValue();

        if (is_array($value)) {
            $value = array_filter($value);
        }

        if (empty($value)) {
            $this->phpValue = $this->getDefaultValue();

            return;
        }


        if (is_array($value)) {
            $this->assignArray($value);

            return;
        }

        if (is_string($value)) {
            $this->assignString($value);

            return;
        }

        throw new InvalidArgumentException("Invalid GeoPoint value, expected string or array");
    }

    /**
     * Initialise les coordonnées du point à partir d'un tableau.
     *
     * @param array $value Les coordonnées du point :
     * - sous la forme d'un tableau associatif de la forme ['lat' => latitude, 'lon' => longitude]
     * - sous la forme d'un tableau numérique de la forme [0 => longitude, 1 => latitude].
     *
     * @throws InvalidArgumentException Si les coordonnées sont incorrectes.
     *
     * @return self $this
     */
    protected function assignArray(array $value)
    {
        if (count($value) !== 2) {
            throw new InvalidArgumentException('Invalid GeoPoint value, expected array with 2 elements');
        }

        // Tableau avec clés alpha, ordre quelconque
        if (isset($value['lat']) && isset($value['lon'])) {
            return $this->setLatitude($value['lat'])->setLongitude($value['lon']);
        }

        // Tableau avec clés numériques : ordre inversé (lon,lat)
        if (isset($value[0]) && isset($value[1])) {
            return $this->setLatitude($value[1])->setLongitude($value[0]);
        }

        throw new InvalidArgumentException('Invalid GeoPoint value, expected array with keys lat/lon or 0/1');
    }

    /**
     * Initialise les coordonnées du point à partir d'une chaine de caractère.
     *
     * @param string $value Les coordonnées du point, sous la forme d'une chaine contenant deux réels séparés
     * par une virgule (les espaces éventuels sont ignorés).
     *
     * @throws InvalidArgumentException Si les coordonnées sont incorrectes.
     *
     * @return self $this
     */
    protected function assignString($value)
    {
        return $this->assignArray(array_reverse(array_map('trim', explode(',', $value))));
    }

    public function getAvailableEditors()
    {
        return [
            'inputs'    => __('Champs texte distincts pour la latitude et la longitude', 'docalist-core'),
            'hiddens'   => __('Champs cachés distincts pour la latitude et la longitude (cachés)', 'docalist-core'),
        ];
    }

    public function getEditorForm($options = null)
    {
        $editor = $this->getOption('editor', $options, $this->getDefaultEditor());
        switch ($editor) {
            case 'inputs':
                $form = new Table();
                $form->input('lat')->addClass('field-latitude')->setLabel(__('Latitude', 'docalist-core'));
                $form->input('lon')->addClass('field-longitude')->setLabel(__('Longitude', 'docalist-core'));
                break;

            case 'hiddens':
                $form = new Div();
                $form->hidden('lat')->addClass('field-latitude');
                $form->hidden('lon')->addClass('field-longitude');
                break;

            default:
                throw new InvalidArgumentException('Invalid GeoPoint editor "' . $editor . '"');
        }

        return $form
            ->setName($this->schema->name())
            ->addClass($this->getEditorClass($editor))
            ->setLabel($this->getOption('label', $options))
            ->setDescription($this->getOption('description', $options));
    }
}
