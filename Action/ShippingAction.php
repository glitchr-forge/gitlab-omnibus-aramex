<?php

namespace Omnibus\Aramex\Action;

use Omnibus\Action\ActionInterface;
use Omnibus\Action\ApiAwareInterface;
use Omnibus\Action\ApiAwareTrait;
use Omnibus\Aramex\Api;
use Omnibus\Aramex\Mapping;
use Omnibus\Exception\CarrierException;
use Omnibus\Model\Label;
use Omnibus\Request\Request;
use Omnibus\Request\Shipping;

/** Shipping's CreateShipments with a label (report 9729 PDF, or ZPL with option label_format). */
final class ShippingAction implements ActionInterface, ApiAwareInterface
{
    /** @use ApiAwareTrait<Api> */
    use ApiAwareTrait;

    public function __construct()
    {
        $this->apiClass = Api::class;
    }

    public function supports(Request $request): bool
    {
        return $request instanceof Shipping;
    }

    public function execute(Request $request): void
    {
        \assert($request instanceof Shipping);
        $s = $request->shipment;
        $international = strtoupper($s->sender->country) !== strtoupper($s->recipient->country);
        $zpl = 'ZPL' === strtoupper((string) $s->option('label_format', 'PDF'));
        $date = $s->shippingDate ?? new \DateTimeImmutable();
        $data = $this->api->call('Shipping', 'CreateShipments', [
            'LabelInfo' => ['ReportID' => $zpl ? 9201 : 9729, 'ReportType' => $zpl ? 'RPT' : 'URL'],
            'Shipments' => [array_filter([
                'Reference1' => $s->reference,
                'Shipper' => Mapping::party($s->sender, $s->reference) + ['AccountNumber' => $this->api->clientInfo()['AccountNumber']],
                'Consignee' => Mapping::party($s->recipient, $s->reference),
                'ShippingDateTime' => '/Date('.($date->getTimestamp() * 1000).')/',
                'DueDate' => '/Date('.($date->modify('+3 days')->getTimestamp() * 1000).')/',
                'Comments' => mb_substr((string) $s->option('instructions', ''), 0, 60),
                'Details' => Mapping::details($s, $s->service ?? ($international ? 'PPX' : 'OND')),
            ])],
        ]);
        $processed = $data['Shipments'][0] ?? [];
        if (!empty($processed['HasErrors'])) {
            throw new CarrierException('aramex', (string) ($processed['Notifications'][0]['Message'] ?? 'Aramex refused the shipment.'), $processed['Notifications'][0]['Code'] ?? null);
        }
        $number = (string) ($processed['ID'] ?? '');
        if ('' === $number) {
            throw new CarrierException('aramex', 'Aramex booked no shipment.');
        }
        $label = $processed['ShipmentLabel'] ?? [];
        $content = isset($label['LabelFileContents']) && \is_string($label['LabelFileContents']) ? base64_decode($label['LabelFileContents']) : null;
        $request->setResult(new Label('aramex', $number, $content, $zpl ? Label::ZPL : Label::PDF, $label['LabelURL'] ?? null, 'https://www.aramex.com/track/results?ShipmentNumber='.rawurlencode($number)));
    }
}
