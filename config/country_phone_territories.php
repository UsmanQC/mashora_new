<?php

/**
 * ISO territory → international dial prefix (digits, no +). Generated from intl-tel-input
 * country data; regenerate with: php database/scripts/build_country_phone_territories.php
 *
 * @return list<array{iso: string, dial: string}>
 */
return json_decode(
    (string) file_get_contents(__DIR__.'/country_phone_territories.json'),
    true,
    512,
    JSON_THROW_ON_ERROR
);
