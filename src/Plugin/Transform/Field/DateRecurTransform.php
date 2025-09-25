<?php

namespace Drupal\premium_calendar\Plugin\Transform\Field;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Url;
use Drupal\date_recur\Plugin\Field\FieldType\DateRecurFieldItemList;
use Drupal\date_recur\Plugin\Field\FieldType\DateRecurItem;
use Drupal\entity_overview\OverviewFilter;
use Drupal\entity_overview_transform\Transform\OverviewResultTransform;
use Drupal\transform_api\FieldTransformBase;

/**
 * @FieldTransform(
 *  id = "date_recur",
 *  label = @Translation("Recurring dates field"),
 *  field_types = {
 *    "date_recur"
 *  }
 * )
 */
class DateRecurTransform extends FieldTransformBase {

  public function transformElements(FieldItemListInterface $items, $langcode) {
    $values = [];
    /** @var DateRecurItem $item */
    foreach ($items as $delta => $item) {
      $values[$delta] = $item->getValue();
    }
    return $values;
  }

}
