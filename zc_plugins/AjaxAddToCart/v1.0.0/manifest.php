<?php

$plugin_folder = basename(dirname(__DIR__));
$version_folder = basename(__DIR__);

$plugin_readme = (defined('DIR_WS_CATALOG') ? DIR_WS_CATALOG : '/') . 'zc_plugins/' . $plugin_folder . '/' . $version_folder . '/readme.html';
$plugin_github = 'https://github.com/zenexpert/ajax-add-to-cart';
$plugin_forum = '';

$button_readme = '<a class="btn btn-primary btn-sm" href="' . $plugin_readme . '" target="_blank"><i class="fa fa-book"></i> Readme</a>';
$button_github = '<a class="btn btn-info btn-sm" href="' . $plugin_github . '" target="_blank"><i class="fa fa-github"></i> GitHub</a>';
$button_forum  = '<a class="btn btn-success btn-sm" href="' . $plugin_forum . '" target="_blank"><i class="fa fa-comments"></i> Forum</a>';

$buttons = '<div class="buttons my-2">' . $button_readme . ' ' . $button_github . ' ' . $button_forum . '</div>';

return [
    'pluginVersion' => '1.0.0',
    'pluginName' => 'Ajax Add To Cart',
    'pluginDescription' => 'Allows adding products to the cart via AJAX without page reloads. Configurable via Admin.' . $buttons,
    'pluginAuthor' => 'ZenExpert',
    'pluginId' => 0,
    'zcVersions' => ['v2.1.0', 'v2.2.0', 'v2.2.2', 'v2.3.0'], // compatible versions of Zen Cart
    'changelog' => '', // online URL (eg github release tag page, or changelog file there) or local filename only, ie: changelog.txt (in same dir as this manifest file)
    'github_repo' => 'https://github.com/zenexpert/ajax-add-to-cart', // url
    'pluginGroups' => [],
];
