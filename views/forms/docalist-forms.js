/**
 * This file is part of Docalist Core.
 *
 * Copyright (C) 2012-2019 Daniel Ménard
 *
 * For copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 *
 * @author Daniel Ménard <daniel.menard@laposte.net>
 */

// Pour la maintenance du code, ce serait bien de découper ce fichier en
// plusieurs fichiers puis de minifier/ré-assembler avec webpack ou autre.

// -----------------------------------------------------------------------------------------------------------------

/**
 * Module - Génère un effet highlight sur l'élément passé en paramètre.
 *
 * L'élément apparaît en jaune clair quelques instants puis s'estompe.
 *
 * On gère l'effet nous-même pour éviter une dépendance envers jquery-ui.
 */
(function ($) {
    $.fn.docalistHighlight = function () {
        // Principe : on crée une div jaune avec exactement les mêmes
        // dimensions que l'élément et on la place au dessus en absolute puis
        // on l'estompe (fadeout) gentiment avant de la supprimer.
        // Adapté de : http://stackoverflow.com/a/13106698
        $(this).each(function () {
            var node = $(this);
            $("<div/>").width(node.outerWidth()).height(node.outerHeight()).css({
                "position" : "absolute",
                "left" : node.offset().left,
                "top" : node.offset().top,
                "background-color" : "#ffff44",
                "opacity" : "0.5",
                "z-index" : "9999999"
            }).appendTo("body").fadeOut(1000, function () {
                $(this).remove();
            });
        });
    };
}(jQuery));

// -----------------------------------------------------------------------------------------------------------------

/**
 * Module - Autosize des textarea (celles d'origine et celles qu'on clone)
 */
jQuery(document).ready(function ($) {
    if (typeof autosize === "function") {
        // Autosize sur les textareas qui existent initiallement dans la page
        autosize($("textarea.autosize"));

        // Autosize sur les textarea qui figurent dans les blocs clonés
        // On intercepte "after-insert-clone" (et non pas "after-clone") car
        // le noeud doit être inséré dasn le DOM pour que autosize détermine
        // correctement la hauteur du texte.
        $(document).on("docalist:forms:after-insert-clone", function (event, node, clone) {
            autosize($("textarea.autosize", clone));
        });
    }
});

//-----------------------------------------------------------------------------------------------------------------

/**
 * Module - clonage des champs répétables.
 *
 * Les boutons doivent avoir la classe "cloner" et ils peuvent avoir les attributs suivant :
 * - "data-clone" : un pseudo sélecteur qui indique l'élément à cloner (par exemple "<^^div.aa"),
 * - "data-level" : indique le niveau auquel on est (1 par défaut).
 */
(function ($, document) {
    /**
     * Lance le clonage quand on clique sur les boutons qui ont la classe "cloner"
     */
    $(document).on("click", ".cloner", function () {
        // 1. Détermine le noeud qu'on doit cloner
        var node = nodeToClone(this);

        // 2. Génère l'événement "before-clone" en passant le noeud qui va être cloné
        $(document).trigger("docalist:forms:before-clone", node);

        // 3. Clone le noeud
        var clone = createClone(node);

        // 4. Renomme les champs (Le niveau auquel on est figure dans l'attribut data-level du bouton)
        var level = parseInt($(this).data("level")) || 1; // NaN ou 0 -> 1
        renumber(clone, level);

        // 5. Génère l'événement "after-clone" en passant le noeud d'origine et le clone
        $(document).trigger("docalist:forms:after-clone", [node, clone]);

        // 6. Insère le clone juste après le noeud d'origine, avec un espace entre deux
        node.after(" ", clone);

        // 7. Génère l'événement "after-insert-clone" en passant le noeud d'origine et le clone
        $(document).trigger("docalist:forms:after-insert-clone", [node, clone]);

        // 7. Donne le focus au premier champ trouvé dans le clone
        var first = clone.is(":input") ? clone : $(":input:first", clone);
        first.is(".selectized") ? first[0].selectize.focus() : first.focus();

        // 8. Fait flasher le clone pour que l'utilisateur voit l'élément inséré
        $(clone).docalistHighlight();
    });

    /**
     * Détermine l'élément à cloner en fonction du bouton "+" qui a été cliqué.
     *
     * Principe : les boutons "+" ont un attribut "data-clone" qui contient un pseudo sélecteur utilisé pour
     * indiquer où se trouve l'élément à cloner.
     * Ce sélecteur est un sélecteur jQuery auquel on peut ajouter (en préfixe) les caractères :
     * - '<' pour dire qu'on veut se déplacer sur le noeud précédent,
     * - '^' pour dire qu'on veut aller au noeud parent.
     * Exemple pour "<^^div.aa"
     * - on a cliqué sur le bouton "+"
     * - aller au noeud qui précède
     * - remonter au grand-parent
     * - sélectionner la div.aa qui figure dans le noeud obtenu.
     *
     * Le sélecteur par défaut est "<" ce qui signifie que si l'attribut data-clone est absent, c'est le noeud
     * qui précède le bouton qui sera cloné.
     *
     * @param   DomElement button le bouton "+" qui a été cliqué
     *
     * @return  DomElement le noeud à cloner
     */
    function nodeToClone(button)
    {
        var node = $(button);

        // Récupère le sélecteur à appliquer (attribut data-clone, '<' par défaut)
        var selector = node.data("clone") || "<";

        // Exécute les commandes contenues dans le préfixe (< = prev, ^ = parent)
        for (var i = 0; i < selector.length; i++) {
            switch (selector.substr(i, 1)) {
            // parent
            case "^":
                node = node.parent();
                continue;

            // previous
            case "<":
                node = node.prev();
                continue;
            }

            // Autre caractère = début du sélecteur jquery
            break;
        }

        // On extrait ce qui reste du sélecteur et on l'applique (si non vide)
        selector = selector.substr(i);
        if (selector.length) {
            node = $(selector, node);
        }

        // node pointe maintenant sur le noeud à cloner
        return node;
    }

    /**
     * Fait un clone du noeud passé en paramètre, supprime les éléments à ignorer et en réinitialise la valeur
     * des champs.
     *
     * @param   DomElement node noeud à cloner
     *
     * @return  DomElement noeud cloné
     */
    function createClone(node)
    {
        // Clone le noeud
        var clone = node.clone();

        // Supprime du clone les éléments qu'il ne faut pas cloner (ceux qui ont la classe .do-not-clone)
        $(".do-not-clone", clone).remove();
        if (clone.is(":input")) {
            clone.addClass("do-not-clone");
            // exemple : si une zone mots-clés contient plusieurs mots-clés,
            // on ne veut pas cloner tous les mots-clés, juste le premier
        }

        // Fait un clear sur tous les champs présents dans le clone (source : http://goo.gl/RE9f1)
        clone.find("input:text, input:password, input:file, select, textarea").addBack().val("");
        clone.find("input:radio, input:checkbox").removeAttr("checked").addBack().removeAttr("selected");

        // Ok
        return clone;
    }

    /**
     * Renumérote les attributs name, id et for présents dans le clone passé en paramètre.
     *
     * - Les attributs id et for sont de la forme "group-i-champ-j-zone-k".
     * - Les attributs name sont de la forme "group[i][champ][j][zone][k]".
     *
     * La méthode va incrémenter soit i, soit j, soit k en fonction de la valeur du level passé en
     * paramètre (1 pour i, 2 pour j, etc.)
     *
     * Par exemple pour "topic[0][term][1]" avec level=2 on obtiendra "topic[0][term][2]".
     *
     * @param   DomElement  clone    le noeud à renuméroter.
     * @param   integer     level   le niveau de renumérotation.
     *
     * @return  DomElement le noeud final.
     */
    function renumber(clone, level)
    {
        $(":input,label,div", clone).addBack().each(function () {
            var input = $(this);

            // Renomme l'attribut name
            $.each(["name"], function (i, name) {
                var value = input.attr(name); // valeur de l'attribut name, id ou for
                if (! value) {
                    return;
                }
                var curLevel = 0;
                value = value.replace(/\[(\d+)\]/g, function (match, i) {
                    if (++curLevel !== level) {
                        return match;
                    }
                    return "[" + (parseInt(i)+1) + "]";
                });
                input.attr(name, value);
            });

            // Renomme les attributs id et for
            $.each(["id", "for", "data-editor"], function (i, name) {
                // dans la liste ci-dessus, data-editor correspond au bouton "add media" de wp-editor
                var value = input.attr(name); // valeur de l'attribut name, id ou for
                if (! value) {
                    return;
                }
                var curLevel = 0;
                value = value.replace(/-(\d+)(-|$)/mg, function (match, i, end) {
                    if (++curLevel !== level) {
                        return match;
                    }
                    return "-" + (parseInt(i)+1) + (end ? "-" : "");
                });
                input.attr(name, value);
            });
        });

        return clone;
    }
}(jQuery, document));


//-----------------------------------------------------------------------------------------------------------------

/**
 * Module - clonage de l'éditeur wordpress / tinymce
 */
(function ($, document) {
    $(document).on("docalist:forms:before-clone", function (event, node) {
        // Désactive les tinymce qui existent dans le noeud d'origine
        $(".wp-editor-area", node).each(function (index, textarea) {
            tinymce.execCommand("mceRemoveEditor",false, textarea.id);
        });
    });

    $(document).on("docalist:forms:after-insert-clone", function (event, node, clone) {
        // Lors du premier appel, WP fait un wp_print_styles('editor-button-css')
        // On ne veut pas cloner ces liens, donc on les supprime
        $("link", clone).remove();

        // Les quicktags ne fonctionnent pas une fois cloné. Si on a une toolbar, on la supprime
        $("div.quicktags-toolbar", clone).remove();

        // Ré-active les tinymce qui existaient dans le noeud d'origine
        $(".wp-editor-area", node).each(function (index, textarea) {
            tinymce.execCommand("mceAddEditor",false, textarea.id);
        });

        // Active les tinymce qui existent dans le clone
        $(".wp-editor-area", clone).each(function (index, textarea) {
            tinymce.execCommand("mceAddEditor",false, textarea.id);

            // pour les quicktags, la config est stockée dans la var globale tinyMCEPreInit
            // le tableaut qtInit contient la config de chacun des éditeurs sous la forme
            // [{id => {id:"dbref-content-0-value", buttons:"strong, em, ..."}}]
            // Dans notre cas, tous les éditeurs ont la même config, donc on prend juste le premier item
            // Mais au final, les quicktags clonés ne fonctionnent pas, dasactivation
            // for (var i in tinyMCEPreInit.qtInit) {
            //     var config = tinyMCEPreInit.qtInit[i];
            //     config.id = textarea.id;
            //     tinyMCEPreInit.qtInit[config.id] = config;
            //     quicktags(config);
            //     break;
            // }
        });
    });
})(jQuery, document);

//-----------------------------------------------------------------------------------------------------------------

/**
 * Module - Contrôle EntryPicker.
 */
(function ($, document) {
    jQuery.fn.docalistEntryPicker = function () {
        return this.each(function () {

            /**
             * Settings du champ EntryPicker
             */
            var settings = $.extend({
                // Type de lookup (table, thesaurus, index, search) ou vide si pas d'ajax
                lookupType: "",
                
                // Source de lookup (nom de la table, du thésaurus, de l'index, etc.) ou vide si pas d'ajax
                lookupSource: "",
                
                // Dans les options, nom du champ qui contient le code de l'option
                valueField: "code",
                
                // Dans les options, nom du champ qui contient le libellé de l'option
                labelField: "label" 
            }, $(this).data());
            
            // Pour les lookups de type "index" les options retournées ont une structure différente 
            if (settings.lookupType === "index") {
                settings.valueField = "text";
                settings.labelField = "text";
            }
            
            /**
             * Libellé des relations
             */
            var relations = {
                USE: "em",
                MT: "mt",
                BT: "tg",
                NT: "ts",
                RT: "ta",
                UF: "ep",
                description: "df",
                SN: "na"
            };

            /**
             * Retourne le libellé à utiliser pour désigner un type de relation donné.
             *
             * Exemple : getRelationLabel("BT"); // -> "tg".
             *
             * @param string type Type de relation.
             *
             * @return string Le libellé à utiliser.
             */
            function getRelationLabel(type) {

                if (relations && relations[type]) {
                    return relations[type];
                }

                return type;
            };

            /**
             * Crée un ID pour le libellé passé en paramètre.
             */
            function createId(label)
            {
                return label;
            };

            /**
             * Lance un lookup ajax pour charger les options qui commencent par la chaine de recherche indiquée.
             *
             *  @param string   search      Chaine recherchée.
             *  @param callable callback    Callback à appeller une fois que les résultats sont obtenus.
             */
            function ajaxLookup(search, callback) {
                // Dans le back-office, la variable ajaxurl est définie par WP et pointe vers la
                // page "wordpress/wp-admin/admin-ajax.php" (cf. http://codex.wordpress.org/AJAX_in_Plugins)
                var url = ajaxurl + "?action=docalist-lookup";

                // Ajoute les paramètres de la requête (cf. LookupManager)
                url += "&type=" + encodeURIComponent(settings.lookupType);
                url += "&source=" + encodeURIComponent(settings.lookupSource);
                if (search.length) {
                    url += "&search=" + encodeURIComponent(search);
                }

                // Lance la requête ajax
                $.ajax({
                    url : url,
                    type: "GET",
                    error: function () {
                        callback();
                    },
                    success: function (res) {
                        for (var i = 0, n = res.length; i < n; i++) {
                            if (! res[i][settings.valueField]) {
                                res[i][settings.valueField] = createId(res[i][settings.labelField]);
                            }
                        }

                        callback(res);
                    }
                });
            };

            /**
             * Génère l'affichage des liens d'un terme pour un type de relation donné.
             *
             * La méthode est appelée avec un code de relation et un objet qui liste les liens pour ce type de
             * relation. Par exemple :
             *
             * renderRelation(
             *     "NT",                            // Type de relation
             *     {
             *         "CODEABS": "Abstinence",     // Première relation de ce type sous la forme code => valeur
             *         "CODEALC": "Alcoolisme"      // Seconde relation
             *     }
             *     function(html){}                 // Escaper
             * );
             *
             * @param string    type        Type de relation.
             * @param object    relations   Liste des relations de ce type.
             * @param callback  escape      Callback à utiliser pour escaper le code html généré.
             *
             * @return string Elle retourne un code html de la forme suivante :
             *
             * <div class="NT">
             *     <i>Termes spécifiques :</i>
             *     <b rel="CODEABS">Abstinence</b>,
             *     <b rel="CODEALC">Alcoolisme</b>
             * </div>
             */
            function renderRelation(type, relations, escape) {
                var all=[];

                $.each(relations, function (code, label) {
                    if (typeof code === "number") {
                        code = createId(label);
                    }
                    all.push("<b rel=\"" + escape(code) + "\">" + escape(label) + "</b>");
                });

                return  "<div class=\"" + escape(type) + "\">"
                    +       "<i>" + escape(getRelationLabel(type)) + "</i> "
                    +       all.join(", ")
                    +   "</div>";
            };

            /**
             * Génère l'affichage d'une option disponible
             *
             * @param object    option      L'option à afficher.
             * @param callback  escape      Callback à utiliser pour escaper le code html généré.
             *
             * @return string Le code html généré.
             */
            function renderOption(option, escape) {
                var html;

                // Cas d'un non-descripteur
                if (option.USE) {
                    // USE est de la forme { "code" : "label }
                    var code = Object.keys(option.USE)[0];
                    html = "<div class=\"nondes\" rel=\"" + escape(code) + "\">";
                    html += "<span class=\"term\" rel=\"" + escape(code) + "\">" + escape(option.label) + "</span>";
                }

                // Cas d'un descripteur
                else {
                    var label = option[settings.labelField].replace(/¤+/g, ', ').replace(/[, ]+$/, '');
                    
                    html = "<div class=\"des\">";
                    html += "<span class=\"term\">" + escape(label) + "</span>";
                }

                /*
                // Description
                if (option.description) {
                    html += "<span class=\"title\" title=\"" + escape(option.description) + "\">?</span>";
                }
                */

                // Description
                if (option.description) {
                    html += "<span class=\"description\" title=\"" + escape(option.description) + "\">?</span>";
                }

                // Scope Note
                if (option.SN) {
                    html += "<span class=\"SN\" title=\"" + escape(option.SN) + "\">!</span>";
                }

                // Relations
                $.each(["USE","MT","BT","NT", "RT","UF"], function (index, field) {
                    if (option[field]) {
                        html += renderRelation(field, option[field], escape);
                    }
                });

                // Fin du terme
                html += "</div>";

                return html;
            }

            /**
             * Initialize selectize sur ce champ
             */
            $(this).selectize({
                // Liste des plugins selectize.js qui sont activés
                plugins: {
                    "subnavigate": {},                  // définit un peu plus bas
                    "drag_drop": {},
                    "remove_button": { "title": "" },   // pour ne pas avoir à gérer les trados
                },

                // Le JSON retourné par "docalist-table-lookup" est de la forme :
                // [ { "code": "xx", "label": "aa" }, { "code": "yy", "label": "bb" } ]

                // Nom du champ qui contient le code des options
                valueField: settings.valueField,

                // Nom du champ qui contient le libellé des options
                labelField: settings.labelField,

                // On ne peut pas ajouter une option non autorisée, sauf pour un lookup sur index (liste ouverte)
                create: settings.lookupType === "index" ? true : false,

                // Charge les options disponibles lorsque le contrôle obtient le focus
                preload: "focus",

                // Crée le popup dans le body plutôt que dans le contrôle
                dropdownParent: "body",

                // Trie les otpions par ordre alpha sur le label
                sortField: settings.labelField,

                // Ajoute la classe "do-not-clone" aux containers créés par selectize
                wrapperClass: "selectize-control do-not-clone",

                // La recherche porte à la fois sur le libellé et sur le code
                searchField: [settings.valueField, settings.labelField],

                // Ne pas mettre en surbrillance la chaine recherchée (ça highlight trop de choses)
                highlight: true,

                // Masque les options déjà sélectionnées
                hideSelected: true,

                // Ouvre le popup automatiquement lorsque le champ obtient le focus
                openOnFocus: true,

                // Fonction utilisée pour charger les options disponibles
                load: settings.lookupType ? ajaxLookup : null,

                // Fonctions de rendu
                render: {
                    option: renderOption,
                    option_create: function(data, escape) {
                        return '<div class="create">Ajouter <strong>' + escape(data.input) + '</strong>&hellip;</div>';
                    }
                }
            });
        });
    };
})(jQuery, document);

//-----------------------------------------------------------------------------------------------------------------

/**
 * Module - Initialise automatiquement les champs qui ont la classe "entrypicker" et gère le clonage.
 */
(function ($) {
    /**
     * Initialise automatiquement les contrôles qui ont la classe "entrypicker".
     */
    jQuery(document).ready(function ($) {
        $("select.entrypicker").docalistEntryPicker();
    });

    $(document).on("docalist:forms:before-clone", function (event, node) {
        // Fait une sauvegarde et appelle destroy sur les entrypicker sur lesquels selectize a été appliqué ,
        $("select.entrypicker.selectized", node).each(function () {
            // Sauvegarde la valeur actuelle dans l'attribut data-save
            $(this).data("save", {
                "value": this.selectize.getValue(),
                "options": this.selectize.options
            }).removeClass("selectized");

            // Quand on var faire "destroy", selectize va réinitialiser le select avec les options d'origine,
            // telles qu'elles figuraient dans le code html initial (sauvegardées dans revertSettings.$children) :
            // 1. si on a une source de lookups, ce sont les options actuellement sélectionnées dans le champ.
            // 2. si c'est un entrypicker sans lookups, c'est la liste complète des options disponibles.
            // Dans le premier cas, cela ne sert à rien de conserver ces options car :
            // - dans le noeud d'origine, on va restaurer notre propre sauvegarde ensuite
            // - dans le clone, on veut un champ "vide", donc on ne veut pas récupérer ces options
            // Donc on efface simplement la sauvegarde faite par selectize. Du coup :
            // - le noeud d'origine se retrouve avec une valeur à vide (mais on va restaurer la sauvegarde ensuite)
            // - le noeud cloné sera vide également (c'est ce qu'on veut)
            // Dans le second cas, par contre, il faut conserver les options.
            if ($(this).data('lookup-source')) {
                this.selectize.revertSettings.$children = [];
            }

            // Supprime selectize du select
            this.selectize.destroy();
        });
    });

    $(document).on("docalist:forms:after-clone", function (event, node, clone) {
        // Réinstalle entrypicker sur les éléments du noeud à cloner et restaure la sauvegarde
        $("select.entrypicker", node).each(function () {
            $(this).docalistEntryPicker();
            var save = $(this).data("save");
            for (var i in save.options) {
                this.selectize.addOption(save.options[i]);
            }
            this.selectize.setValue(save.value);
        });

        // Installe selectize sur les éléments du clone
        $("select.entrypicker", clone).docalistEntryPicker();
    });
})(jQuery);

//-----------------------------------------------------------------------------------------------------------------

/**
 * Plugin pour Selectize permettant de naviguer dans un thesaurus.
 */
(function ($) {
    Selectize.define("subnavigate", function (options) {
        var self = this;

        /**
         * Surcharge la méthode onOptionSelect
         *
         * On regarde
         * - si le tag qui a été cliqué dans l'option en cours a un attribut href
         * - si l'option en cours a un attribut href
         *
         * Si c'est le cas, on relance une recherche par valeur sur le contenu de
         * l'attribut href et on affiche les résultats obtenus, sinon, on appelle
         * la méthode onOptionSelect d'origine.
         */
        self.onOptionSelect = (function () {
            var original = self.onOptionSelect;
    
            var show = function (value) {
                if (!self.options.hasOwnProperty(value)) {
                    return;
                }
    
                var option = self.options[value];
                var html = self.render("option", option);
                self.$dropdown_content.html(html);
            };

            var loaded = function (value, results) {
                if (results && results.length) {
                    self.addOption(results);
                }
                show(value);
            };

            return function (e) {
                // soit selectize nous a passé un event mouse ($dropdown.on("mousedown"))
                // soit un objet contenant juste currentTarget (onKeyDown:KEY_RETURN)

                // Teste si c'est un lien
                var target = e.target || e.currentTarget;
                var value = $(target).attr("rel");

                // On a trouvé un lien
                if (value) {
                    // Empêche la fermeture du dropdown
                    e.preventDefault && e.preventDefault();
                    e.stopPropagation && e.stopPropagation();
    
                    if (self.options.hasOwnProperty(value)) {
                        show(value);
                    } else {
                        var load = self.settings.load;
                        if (!load) {
                            return;
                        }
                        load.apply(self, ["[" + value + "]", function (results) {
                            loaded(value, results);
                        }]);
                    }
                    return false;
                }

                // Ce n'est pas un lien, laisse la méthode d'origine s'exécuter
                else {
                    return original.apply(this, arguments);
                }
            };
        })();
    });
})(jQuery);
