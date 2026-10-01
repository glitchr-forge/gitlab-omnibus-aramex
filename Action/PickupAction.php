<?php

namespace Omnibus\Aramex\Action;

use Omnibus\Action\ActionInterface;
use Omnibus\Action\ApiAwareInterface;
use Omnibus\Action\ApiAwareTrait;
use Omnibus\Aramex\Api;
use Omnibus\Model\Address;
use Omnibus\Model\PickupPoint;
use Omnibus\Request\Pickup;
use Omnibus\Request\Request;

/** Location's FetchOffices: Aramex offices in the country, nearest city first. */
final class PickupAction implements ActionInterface, ApiAwareInterface
{
    /** @use ApiAwareTrait<Api> */
    use ApiAwareTrait;

    public function __construct()
    {
        $this->apiClass = Api::class;
    }

    public function supports(Request $request): bool
    {
        return $request instanceof Pickup;
    }

    public function execute(Request $request): void
    {
        \assert($request instanceof Pickup);
        $near = $request->near;
        $data = $this->api->call('Location', 'FetchOffices', ['CountryCode' => strtoupper($near->country)]);
        $points = [];
        foreach ($data['Offices'] ?? [] as $office) {
            $a = $office['OfficeAddress'] ?? [];
            $points[] = new PickupPoint('aramex', (string) ($office['Entity'] ?? $office['OfficeLocation'] ?? ''), (string) ($office['OfficeLocation'] ?? 'Aramex'),
                new Address((string) ($office['OfficeLocation'] ?? ''), array_values(array_filter([$a['Line1'] ?? null, $a['Line2'] ?? null])), (string) ($a['PostCode'] ?? ''), (string) ($a['City'] ?? ''), (string) ($a['CountryCode'] ?? $near->country)),
                isset($a['Latitude']) ? (float) $a['Latitude'] : null, isset($a['Longitude']) ? (float) $a['Longitude'] : null);
        }
        usort($points, static fn (PickupPoint $x, PickupPoint $y) => (0 === strcasecmp($y->address->city, $near->city)) <=> (0 === strcasecmp($x->address->city, $near->city)));
        $request->setResult(\array_slice($points, 0, $request->limit));
    }
}
