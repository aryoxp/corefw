<?php

class QBRaw {
    public $raw = '';
    public function __construct( $raw ) {
        $this->raw = $raw;
    }

    public static function raw($raw) {
      return new QBRaw($raw);
    }
}