<?php
/**
 * This file contains only a single class.
 *
 * @file
 * @package CMS
 */

namespace WordPress\CMS\Models;

class Programs extends BaseModel
{
  public $table = 'programs';
  public $slug = 'program';
  public $prefix = 'at-a-glance-';
}