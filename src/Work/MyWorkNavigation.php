<?php

declare(strict_types=1);

namespace App\Work;

use AaiEduHr\HeartPhrameModuleAuth\Account\AuthAccountNavigationProviderInterface;
use HeartPhrame\Localization\TranslatorInterface;
use HeartPhrame\Routing\UrlGenerator;

/**
 * EN: Adds a personal menu destination without changing global or Workspace navigation settings.
 *
 * HR: Dodaje osobno odredište izbornika bez promjene globalnih ili područnih postavki navigacije.
 */
final readonly class MyWorkNavigation implements AuthAccountNavigationProviderInterface
{
    /**
     * EN: Receives routing and the current interface language without querying content inventories.
     *
     * HR: Prima rute i aktualni jezik sučelja bez upita nad popisima sadržaja.
     */
    public function __construct(private UrlGenerator $urls, private TranslatorInterface $translator)
    {
    }

    /**
     * EN: Guests get no personal link; the destination independently requires an authenticated session.
     *
     * HR: Gosti ne dobivaju osobnu poveznicu; odredište zasebno zahtijeva prijavljenu sjednicu.
     *
     * @return list<array{key:string,label:string,path:string,order:int}>
     */
    public function itemsForUser(int $userId): array
    {
        return $userId < 1 ? [] : [['key' => 'simbioza.my-work', 'label' => $this->translator->trans('Moj rad'),
            'path' => $this->urls->getPathFor('work.index'), 'order' => 15]];
    }
}
