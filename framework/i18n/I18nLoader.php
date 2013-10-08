<?php
namespace regenix\i18n;

interface I18nLoader {

    /**
     * @param string $lang
     * @return mixed
     */
    public function loadLang($lang);

    /**
     * @param string $lang
     * @return int
     */
    public function getLastUpdate($lang);
}