<?php
namespace Drupal\premium_calendar;

use Drupal\block_content\Entity\BlockContent;
use Drupal\Core\Field\EntityReferenceFieldItemList;

class CalendarService {

  function getCalendarOptions(BlockContent $block) {
    $settings = ['options' => []];
    $settings['options']['locale'] = \Drupal::languageManager()->getCurrentLanguage()->getId();
    $settings['options']['timeZone'] = date_default_timezone_get();
    $settings['options']['initialView'] = str_replace('calendar--', '', $block->get('field_display')->first()->getString());
    $settings['options']['eventSources'] = [];
    $field = $block->get('field_event_types');
    if ($field instanceof EntityReferenceFieldItemList) {
      $terms = $field->referencedEntities();
      /** @var \Drupal\taxonomy\Entity\Term $term */
      foreach ($terms as $term) {
        $settings['options']['eventSources'][] = [
          'url' => '/calendar/type/' . $term->id(),
          'color' => $term->get('field_background_color')->getString(),
          'textColor' => $term->get('field_text_color')->getString()
        ];
      }
    }

    return $settings;
  }

}
