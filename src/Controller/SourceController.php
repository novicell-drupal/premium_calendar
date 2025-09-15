<?php
namespace Drupal\premium_calendar\Controller;

use DateTimeInterface;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Entity\EntityAccessControlHandlerInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Render\RendererInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\date_recur\Plugin\Field\FieldType\DateRecurItem;
use Drupal\forum\ForumManagerInterface;
use Drupal\node\NodeStorageInterface;
use Drupal\taxonomy\TermInterface;
use Drupal\taxonomy\TermStorageInterface;
use Drupal\taxonomy\VocabularyStorageInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;

/**
 * Controller routines for calendar routes.
 */
class SourceController extends ControllerBase {

  /**
   * Constructs a SourceController object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   Entity type manager.
   */
  public function __construct(EntityTypeManagerInterface $entityTypeManager) {
    $this->entityTypeManager = $entityTypeManager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager')
    );
  }

  public function eventsOfType(TermInterface $taxonomy_term) {
    $storage = $this->entityTypeManager()->getStorage('node');

    $node_ids = $storage->getQuery()
      ->condition('field_event_type', [$taxonomy_term->id()], 'IN')
      ->accessCheck()
      ->execute();

    $nodes = $storage->loadMultiple($node_ids);

    $events = [];
    /** @var \Drupal\node\Entity\Node $node */
    foreach ($nodes as $id => $node) {
      /** @var DateRecurItem $date_recur */
      $date_recur = $node->get('field_date_recur')->first();
      $start_end_date = [
        'type' => 'date_recur',
        'start' => $date_recur->start_date->format(DateTimeInterface::ATOM),
        'end' => NULL
      ];
      $until = clone $date_recur->end_date;
      if (!is_null($date_recur->end_date)) {
        $start_end_date['end'] = $date_recur->end_date->format(DateTimeInterface::ATOM);
        $until->add(new \DateInterval('P1Y'));
      }
      if ($node->get('field_event_display')->isEmpty()) {
        $eventDisplay = 'none';
      } else {
        $eventDisplay = str_replace('event--', '', $node->get('field_event_display')->first()->getString());
      }
      if ($date_recur->isRecurring()) {
        foreach ($date_recur->getHelper()->getOccurrences(NULL, $until->getPhpDateTime()) as $key => $occurrence) {
          $event = [
            'id' => $node->id() . '-' . $key,
            'groupId' => $node->id(),
            'title' => $node->label(),
            'start' => $occurrence->getStart()->format(DateTimeInterface::ATOM),
            'end' => $occurrence->getEnd()->format(DateTimeInterface::ATOM),
            'allDay' => $this->isAllDayEvent($start_end_date),
            'display' => $eventDisplay,
            'url' => $node->toUrl()->toString()
          ];
          $events[] = $event;
        }
      } else {
        $event = [
          'id' => $node->id(),
          'title' => $node->label(),
          'start' => $start_end_date['start'],
          'end' => $start_end_date['end'],
          'allDay' => $this->isAllDayEvent($start_end_date),
          'display' => $eventDisplay,
          'url' => $node->toUrl()->toString()
        ];
        $events[] = $event;
      }
    }

    return new JsonResponse($events);
  }

  /**
   * Check whether this is an all-day event.
   *
   * @param array $start_end_date
   *   Array of the start/end dates for the event.
   *
   * @return bool
   *   TRUE, if all day, otherwise FALSE.
   */
  protected function isAllDayEvent(array $start_end_date): bool {
    if (empty($start_end_date['end'])) {
      $allDay = TRUE;
    }
    else {
      $allDay = FALSE;
      switch ($start_end_date['type']) {
        case 'smartdate':
          $start_time = substr($start_end_date['start'], 11, 5);
          $end_time = substr($start_end_date['end'], 11, 5);
          if ($start_time === '00:00' && $end_time === '23:59') {
            $allDay = TRUE;
          }
          break;

        default:
          $start_time = substr($start_end_date['start'], 11, 8);
          $end_time = substr($start_end_date['end'], 11, 8);
          if ($start_time === '00:00:00' && $end_time === '23:59:59') {
            $allDay = TRUE;
          }
      }
    }

    return $allDay;
  }

}
