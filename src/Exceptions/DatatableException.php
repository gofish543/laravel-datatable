<?php

namespace Dykhuizen\Datatable\Exceptions;

use Exception;

class DatatableException extends Exception {

    public function __construct($message = '', $code = -1, Exception $previous = null) {
        switch ($code) {
            case 0:
                $message = 'Invalid argument.';
                break;
            case 1:
                $message = 'Relation \'' . $message . '\' does not exist.';
                break;
            case 2:
                $message = 'Relation \'' . $message . '\' is not instance of HasOne or BelongsTo.'; //hasMany
                break;
            default:
                break;
        }

        parent::__construct($message, $code, $previous);
    }
}
