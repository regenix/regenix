<?php
/**
 * Author: Dmitriy Zayceff
 * E-mail: dz@dim-s.net
 * Date: 07.04.13
 */

namespace framework\exceptions;


abstract class ResponseException extends CoreException {

    /** @return int */
    abstract function getStatus();
}