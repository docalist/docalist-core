<?php
use Docalist\Forms\Form;

$form = new Form();
$form->setLabel('Saisie/modification d\'une notice documentaire');
$form->addClass('form-horizontal');

// -----------------------------------------------------------------------------

$box = $form->fieldset()->setLabel('Nature du document');

$box->select('type')
    ->setLabel('Type de document')
    ->setOptions(array('article','livre','rapport'));

$box->checklist('genre')
    ->setLabel('Genre de document')
    ->setOptions(array('communication','decret','didacticiel','etat de l\'art'));

$box->checklist('media')
    ->setLabel('Support de document')
    ->setOptions(array('cd-rom','internet','papier','dvd','vhs'));

// -----------------------------------------------------------------------------

$box = $form->fieldset()->setLabel('Titre du document');

$box->input('title')
    ->addClass('span12')
    ->setLabel('Titre principal');

$box->table('othertitle')
    ->setLabel('Autres titres')
    ->setRepeatable(true)
        ->select('type')
        ->setLabel('Type de titre')
        ->setOptions(array('serie','dossier','special'))
        ->addClass('span4')
    ->getParent()
        ->input('title')
        ->setLabel('Autre titre')
        ->setDescription('En minuscules, svp')
        ->addClass('span8')
        ;

$box->table('translation')
    ->setLabel('Traduction du titre')
    ->setRepeatable(true)
        ->select('language')
        ->setLabel('Langue')
        ->setOptions(array('fre','eng','ita','spa','deu'))
        ->addClass('span4')
    ->getParent()
        ->input('title')
        ->setLabel('Titre traduit')
        ->addClass('span8')
        ;

// -----------------------------------------------------------------------------

$box = $form->fieldset()->setLabel('Auteurs');

$box->table('author')
    ->setLabel('Personnes')
    ->setRepeatable(true)
        ->input('name')
        ->setLabel('Nom')
        ->addClass('span5')
    ->getParent()
        ->input('firstname')
        ->setLabel('Prénom')
        ->addClass('span4')
    ->getParent()
        ->select('role')
        ->setLabel('Rôle')
        ->setOptions(array('pref','trad','ill','dir','coord','postf','intro'))
        ->addClass('span3')
        ;

$box->table('organisation')
    ->setLabel('Organismes')
    ->setRepeatable(true)
        ->input('name')
        ->setLabel('Nom')
        ->addClass('span5')
    ->getParent()
        ->input('city')
        ->setLabel('Ville')
        ->addClass('span3')
    ->getParent()
        ->select('country')
        ->setLabel('Pays')
        ->setOptions(array('france', 'usa', 'espagne', 'italie'))
        ->addClass('span2')
    ->getParent()
        ->select('role')
        ->setLabel('Rôle')
        ->setOptions(array('com','financ'))
        ->addClass('span2')
        ;

// -----------------------------------------------------------------------------

$box = $form->fieldset()->setLabel('Journal');

$box->input('journal')
    ->setLabel('Titre de périodique')
    ->addClass('span12')
    ->setDescription('Nom du journal dans lequel a été publié le document.');

$box->input('issn')
    ->setLabel('ISSN')
    ->addClass('span6')
    ->setDescription('International Standard Serial Number : identifiant unique du journal.');

$box->input('volume')
    ->setLabel('Numéro de volume')
    ->addClass('span4')
    ->setDescription('Numéro de volume pour les périodiques, tome pour les monographies.');

$box->input('issue')
    ->setLabel('Numéro de fascicule')
    ->addClass('span4')
    ->setDescription('Numéro de la revue dans lequel le document a été publié.');

// -----------------------------------------------------------------------------

$box = $form->fieldset()->setLabel('Informations bibliographiques');

$box->input('date')
    ->setLabel('Date de publication')
    ->addClass('span6')
    ->setDescription('Date d\'édition ou de diffusion du document.');

$box->select('language')
    ->setLabel('Langue du document')
    ->setRepeatable(true)
    ->setDescription('Langue(s) dans laquelle est écrit le document.')
    ->addClass('span6')
    ->setOptions(array('fre','eng','ita','spa','deu'));

$box->input('pagination')
    ->setLabel('Pagination')
    ->addClass('span6')
    ->setDescription('Pages de début et de fin (ex. 15-20) ou nombre de pages (ex. 10p.) du document.');

$box->input('format')
    ->setLabel('Format du document')
    ->addClass('span12')
    ->setDescription('Caractéristiques matérielles du document : étiquettes de collation (tabl, ann, fig...), références bibliographiques, etc.');

// -----------------------------------------------------------------------------

$box = $form->fieldset()->setLabel('Informations éditeur');

$box->table('editor')
    ->setLabel('Editeur')
    ->setDescription('Editeur et lieu d\'édition.')
    ->setRepeatable(true)
        ->input('name')
        ->setLabel('Nom')
        ->addClass('span5')
    ->getParent()
        ->input('city')
        ->setLabel('Ville')
        ->addClass('span5')
    ->getParent()
        ->select('country')
        ->setLabel('Pays')
        ->addClass('span2')
        ->setOptions(array('france', 'usa', 'espagne', 'italie'));

$box->table('collection')
    ->setLabel('Collection')
    ->setDescription('Collection et numéro au sein de cette collection du document catalogué.')
    ->setRepeatable(true)
        ->input('name')
        ->setLabel('Nom')
        ->addClass('span9')
    ->getParent()
        ->input('number')
        ->addClass('span3')
        ->setLabel('Numéro dans la collection');

$box->table('edition')
    ->setLabel('Mentions d\'édition')
    ->setDescription('Mentions d\'éditions (hors série, 2nde édition, etc.) et autres numéros du document (n° de rapport, de loi, etc.)')
    ->setRepeatable(true)
        ->input('type')
        ->setLabel('Mention')
        ->addClass('span9')
    ->getParent()
        ->input('value')
        ->setLabel('Numéro')
        ->addClass('span3')
        ;

$box->input('isbn')
    ->setLabel('ISBN')
    ->addClass('span6')
    ->setDescription('International Standard Book Number : identifiant unique pour les livres publiés.');

// -----------------------------------------------------------------------------

$box = $form->fieldset()->setLabel('Congrès et diplômes');

$box->table('event')
    ->setLabel('Informations sur l\'événement')
    ->setDescription('Congrès, colloques, manifestations, soutenances de thèse, etc.')
        ->input('title')
        ->setLabel('Titre')
        ->addClass('span5')
    ->getParent()
        ->input('date')
        ->setLabel('Date')
        ->addClass('span2')
    ->getParent()
        ->input('place')
        ->setLabel('Lieu')
        ->addClass('span3')
    ->getParent()
        ->input('number')
        ->setLabel('N°')
        ->addClass('span2')
        ;

$box->table('degree')
    ->setLabel('Diplôme')
    ->setDescription('Description des titres universitaires et professionnels.')
        ->select('level')
        ->setLabel('Niveau')
        ->addClass('span3')
        ->setOptions(array('licence','master','doctorat'))
    ->getParent()
        ->input('title')
        ->setLabel('Intitulé')
        ->addClass('span9');

// -----------------------------------------------------------------------------

$box = $form->fieldset()->setLabel('Indexation et résumé');

$box->table('topic')
    ->setLabel('Mots-clés')
    ->setDescription('Indexation du document : mots-clés matières, mots outils, noms propres, description géographique, période historique, candidats descripteurs, etc.', false)
    ->setRepeatable(true)
        ->select('type')
        ->setLabel('Thesaurus')
        ->addClass('span2')
        ->setOptions(array('theso un', 'theso deux', 'theso trois'))
    ->getParent()
        ->table()
        ->setAttribute('style', 'border: 1px solid red')
        ->setLabel('Termes')
        ->addClass('span12')
            ->input('terms')
            ->addClass('span2')
            ->setRepeatable(true);

$box->table('abstract')
    ->setLabel('Résumé')
    ->setDescription('Résumé du document et langue du résumé.')
    ->setRepeatable(true)
        ->select('language')
        ->setLabel('Langue du résumé')
        ->addClass('span2')
        ->setOptions(array('fre','eng','ita','spa','deu'))
    ->getParent()
        ->textarea('content')
        ->setLabel('Résumé')
        ->addClass('span10')
        ;

$box->table('note')
    ->setLabel('Notes')
    ->setDescription('Remarques, notes et informations complémentaires sur le document.')
    ->setRepeatable(true)
        ->select('type')
        ->setLabel('Type de note')
        ->addClass('span2')
        ->setOptions(array('note visible','note interne','avertissement','objectifs pédagogiques','publics concernés','pré-requis', 'modalités d\'accès', 'copyright'))
    ->getParent()
        ->textarea('content')
        ->setLabel('Contenu de la note')
        ->addClass('span10');

// -----------------------------------------------------------------------------

$box = $form->fieldset()->setLabel('Informations de gestion');

$box->input('ref')
    ->setLabel('Numéro de référence')
    ->addClass('span2')
    ->setDescription('Numéro unique identifiant la notice.');

$box->input('owner')
    ->setLabel('Propriétaire de la notice')
    ->addClass('span2')
    ->setDescription('Personne ou centre de documentation qui a produit la notice.')
    ->setRepeatable(true);

$box->table('creation')
    ->setLabel('Date de création')
    ->setDescription('Date de création de la notice.')
        ->input('date')
        ->setLabel('Le')
        ->addClass('span2')
    ->getParent()
        ->input('by')
        ->setLabel('Par')
        ->addClass('span2')
        ;

$box->table('lastupdate')
    ->setLabel('Dernière modification')
    ->setDescription('Date de dernière mise à jour de la notice.')
        ->input('date')
        ->setLabel('Le')
        ->addClass('span2')
    ->getParent()
        ->input('by')
        ->setLabel('Par')
        ->addClass('span2')
        ;

// -----------------------------------------------------------------------------
$form->submit('Go !');

return $form;
