<?php

namespace Omnibus\Aramex\Action;

use Omnibus\Action\ActionInterface;
use Omnibus\Action\ApiAwareInterface;
use Omnibus\Action\ApiAwareTrait;
use Omnibus\Aramex\Api;
use Omnibus\Aramex\Mapping;
use Omnibus\Model\Tracking as TrackingModel;
use Omnibus\Model\TrackingEvent;
use Omnibus\Model\TrackingStatus;
use Omnibus\Request\Request;
use Omnibus\Request\Tracking;

/** Tracking's TrackShipments: the updates, oldest first. */
final class TrackingAction implements ActionInterface, ApiAwareInterface
{
    /** @use ApiAwareTrait<Api> */
    use ApiAwareTrait;

    public function __construct()
    {
        $this->apiClass = Api::class;
    }

    public function supports(Request $request): bool
    {
        return $request instanceof Tracking;
    }

    public function execute(Request $request): void
    {
        \assert($request instanceof Tracking);
        $data = $this->api->call('Tracking', 'TrackShipments', ['Shipments' => [$request->trackingNumber], 'GetLastTrackingUpdateOnly' => false]);
        $events = [];
        foreach ($data['TrackingResults'] ?? [] as $result) {
            foreach ($result['Value'] ?? [$result] as $update) {
                $at = $update['UpdateDateTime'] ?? '';
                $at = preg_match('#/Date\((-?\d+)#', (string) $at, $m) ? (new \DateTimeImmutable())->setTimestamp((int) ($m[1] / 1000)) : new \DateTimeImmutable($at ?: 'now');
                $events[] = new TrackingEvent($at, Mapping::status($update['UpdateCode'] ?? null, $update['UpdateDescription'] ?? null), (string) ($update['UpdateDescription'] ?? ''), $update['UpdateLocation'] ?? null, $update['UpdateCode'] ?? null);
            }
        }
        usort($events, static fn (TrackingEvent $a, TrackingEvent $b) => $a->at <=> $b->at);
        $request->setResult(new TrackingModel('aramex', $request->trackingNumber, $events ? $events[array_key_last($events)]->status : TrackingStatus::UNKNOWN, $events));
    }
}
