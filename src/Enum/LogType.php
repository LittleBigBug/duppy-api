<?php
/*
 *                  This file is part of Duppy Suite
 *                         https://dup.drm.gg
 *                               -= * =-
 */

namespace Duppy\Enum;

use Spatie\Enum\Enum;

/**
 * Enum used for Duppy\Entities\Log Logging types.
 *
 * @method static self info()
 * @method static self error()
 * @method static self temp()
 * @method static self mail()
 *
 * @package Duppy\Enum
 */
class LogType extends Enum { }