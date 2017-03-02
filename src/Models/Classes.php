<?php
/**
 * This file contains only a single class.
 *
 * @file
 * @package CMS
 */

namespace WordPress\CMS\Models;

class Classes extends BaseModel
{
  public $table = 'classes';
  public $slug = 'class';
  public $prefix = 'at-a-glance-';
}