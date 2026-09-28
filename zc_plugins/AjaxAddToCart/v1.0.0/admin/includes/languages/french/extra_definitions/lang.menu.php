<?php

global $installedPlugins;

$define = [
    'ADMIN_PLUGIN_MANAGER_NAME_FOR_AJAXADDTOCART' => 'Ajout au panier via Ajax',
    'ADMIN_PLUGIN_MANAGER_DESCRIPTION_FOR_AJAXADDTOCART' => 'Permet d’ajouter des produits au panier via AJAX sans recharger la page. Configurable depuis l’administration.' .
    '<div class="buttons my-2">' .
    '<a class="btn btn-primary btn-sm" href="' . DIR_WS_CATALOG . 'zc_plugins/AjaxAddToCart/' . $installedPlugins['AjaxAddToCart']['version'] . '/readme.html " target="_blank"><i class="fa fa-book"></i> Lisez-moi</a>' .
    '<a class="btn btn-info btn-sm" href="https://github.com/zenexpert/ajax-add-to-cart" target="_blank"><i class="fa fa-github-square"></i> GitHub</a>' .
    '<a class="btn btn-success btn-sm" href="" target="_blank"><i class="fa fa-comments"></i> Forum</a>' .
    '</div>',
// AjaxAddToCart - configuration
    'CFGTITLE_AJAX_ADD_TO_CART_STATUS' => 'Activer l’ajout au panier via Ajax',
    'CFGDESC_AJAX_ADD_TO_CART_STATUS' => 'Activer ou désactiver la fonctionnalité d’ajout au panier via Ajax ?',
    'CFGTITLE_AJAX_ADD_TO_CART_FORM_SELECTOR' => 'Sélecteur de formulaire',
    'CFGDESC_AJAX_ADD_TO_CART_FORM_SELECTOR' => 'Sélecteur jQuery pour les formulaires d’ajout au panier.',
    'CFGTITLE_AJAX_ADD_TO_CART_BUTTON_SELECTOR' => 'Sélecteur de bouton',
    'CFGDESC_AJAX_ADD_TO_CART_BUTTON_SELECTOR' => 'Sélecteur jQuery pour les boutons de soumission à l’intérieur des formulaires.',
    'CFGTITLE_AJAX_ADD_TO_CART_LINK_SELECTOR' => 'Sélecteur de lien',
    'CFGDESC_AJAX_ADD_TO_CART_LINK_SELECTOR' => 'Sélecteur jQuery pour les liens d’achat immédiat directs.',
    'CFGTITLE_AJAX_ADD_TO_CART_CART_COUNT_SELECTOR' => 'Sélecteur de quantité pour le panier',
    'CFGDESC_AJAX_ADD_TO_CART_CART_COUNT_SELECTOR' => 'Sélecteur jQuery pour les éléments affichant le nombre d’articles dans le panier (laisser vide s’il n’y en a pas).',
    'CFGTITLE_AJAX_ADD_TO_CART_CART_TOTAL_SELECTOR' => 'Sélecteur du total du panier',
    'CFGDESC_AJAX_ADD_TO_CART_CART_TOTAL_SELECTOR' => 'Sélecteur jQuery pour les éléments affichant le montant total du panier (laisser vide s’il n’y en a pas).',
    'CFGTITLE_AJAX_ADD_TO_CART_SIDEBOX_SELECTOR' => 'Sélecteur de bloc latéral',
    'CFGDESC_AJAX_ADD_TO_CART_SIDEBOX_SELECTOR' => 'Sélecteur jQuery pour le conteneur du bloc latéral du panier.',
    'CFGTITLE_AJAX_ADD_TO_CART_IN_CART_SELECTOR' => 'Sélecteur de contenu du panier',
    'CFGDESC_AJAX_ADD_TO_CART_IN_CART_SELECTOR' => 'Sélecteur jQuery pour l’élément « Quantité dans le panier » sur la page d’informations du produit.',
    'CFGTITLE_AJAX_ADD_TO_CART_IN_CART_PARENT' => 'Balise parente du contenu du panier',
    'CFGDESC_AJAX_ADD_TO_CART_IN_CART_PARENT' => 'Sélecteur jQuery pour le conteneur parent où l’élément « Quantité dans le panier » doit être injecté s’il n’existe pas déjà.',
// AjaxAddToCart - configuration_group
    'CFG_GRP_TITLE_AJAX_ADD_TO_CART' => '%%BOX_CONFIGURATION_AJAX_ADD_TO_CART%%',
    'CFG_GRP_DESC_PAJAX_ADD_TO_CART' => 'Paramètres de configuration du plugin « %%BOX_CONFIGURATION_AJAX_ADD_TO_CART%% »',
];

return $define;
