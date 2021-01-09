<?php
/*
 *                  This file is part of Duppy Suite
 *                         https://dup.drm.gg
 *                               -= * =-
 */

namespace Duppy\Enum;

use Spatie\Enum\Enum;

/**
 * Generic Enum that can be used for Duppy API error codes, being returned or in a DuppyException
 *
 * @method static self unknown()
 * @method static self noneFound()
 *
 * @package Duppy\Enum
 */
class DuppyError extends Enum { }