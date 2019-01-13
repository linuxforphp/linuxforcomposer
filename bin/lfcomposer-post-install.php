<?php

$preUpdateCmd = [
    'php -r "if (file_exists(\'vendor/composer/linuxforcomposer.pid\')) {copy(\'vendor/composer/linuxforcomposer.pid\', \'linuxforcomposer.pid\');}"'
];

$postUpdateCmd = [
    'php -r "if (file_exists(\'linuxforcomposer.pid\')) {rename(\'linuxforcomposer.pid\', \'vendor/composer/linuxforcomposer.pid\');}"'
];

$configArray = json_decode(file_get_contents(BASEDIR . DIRECTORY_SEPARATOR . 'composer.json'), true);

if (array_key_exists('scripts', $configArray)&& array_key_exists('pre-update-cmd', $configArray['scripts'])) {
    if (!is_array($configArray['scripts']['pre-update-cmd'])) {
        $arrayEntry = $configArray['scripts']['pre-update-cmd'];
        $configArray['scripts']['pre-update-cmd'] = [$arrayEntry];
    }

    $configArray['scripts']['pre-update-cmd'] = array_merge($configArray['scripts']['pre-update-cmd'], $preUpdateCmd);
} else {
    $configArray['scripts']['pre-update-cmd'] = $preUpdateCmd;
}



if (array_key_exists('scripts', $configArray)&& array_key_exists('post-update-cmd', $configArray['scripts'])) {
    if (!is_array($configArray['scripts']['post-update-cmd'])) {
        $arrayEntry = $configArray['scripts']['post-update-cmd'];
        $configArray['scripts']['post-update-cmd'] = [$arrayEntry];
    }

    $configArray['scripts']['post-update-cmd'] = array_merge($configArray['scripts']['post-update-cmd'], $postUpdateCmd);
} else {
    $configArray['scripts']['post-update-cmd'] = $postUpdateCmd;
}


file_put_contents(
    BASEDIR . DIRECTORY_SEPARATOR . 'composer.json',
    json_encode(
        $configArray,
        JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE
    )
);