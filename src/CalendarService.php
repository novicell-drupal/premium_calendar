<?php
namespace Drupal\premium_calendar;

use Drupal\block_content\Entity\BlockContent;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Field\EntityReferenceFieldItemList;
use Drupal\Core\Language\LanguageManagerInterface;

class CalendarService {

  /**
   * @var \Drupal\Core\Language\LanguageManagerInterface
   */
  private LanguageManagerInterface $languageManager;

  /**
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  private ModuleHandlerInterface $moduleHandler;

  function __construct(LanguageManagerInterface $languageManager, ModuleHandlerInterface $moduleHandler) {
    $this->languageManager = $languageManager;
    $this->moduleHandler = $moduleHandler;
  }

  function getCalendarOptions(BlockContent $block) {
    $settings = ['options' => []];
    $settings['options']['locale'] = $this->languageManager->getCurrentLanguage()->getId();
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
    $this->moduleHandler->alter('calendar_options', $settings);

    return $settings;
  }

}
