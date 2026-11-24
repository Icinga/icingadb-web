<?php

// SPDX-FileCopyrightText: 2019 Icinga GmbH <https://icinga.com>
// SPDX-License-Identifier: GPL-3.0-or-later

namespace Icinga\Module\Icingadb\Controllers;

use Icinga\Exception\NotFoundError;
use Icinga\Module\Icingadb\Common\EmbeddedList;
use Icinga\Module\Icingadb\Model\User;
use Icinga\Module\Icingadb\Model\Usergroup;
use Icinga\Module\Icingadb\Web\Control\SearchBar\ObjectSuggestions;
use Icinga\Module\Icingadb\Web\Controller;
use Icinga\Module\Icingadb\Widget\Detail\ObjectHeader;
use Icinga\Module\Icingadb\Widget\Detail\UserDetail;
use ipl\Stdlib\Filter;
use ipl\Web\Control\LimitControl;
use ipl\Web\Control\SortControl;

class ContactController extends Controller
{
    use EmbeddedList;

    /** @var User The user object */
    protected $user;

    public function init()
    {
        $this->assertRouteAccess('contacts');

        $this->addTitleTab(t('Contact'));

        $name = $this->params->shiftRequired('name');

        $query = User::on($this->getDb())->with('timeperiod');
        $query->filter(Filter::equal('user.name', $name));

        $this->applyRestrictions($query);

        $user = $query->first();
        if ($user === null) {
            throw new NotFoundError(t('Contact not found'));
        }

        $this->user = $user;
        $this->setTitle($user->display_name);
    }

    public function indexAction()
    {
        $this->setAutorefreshInterval(10);

        $groups = Usergroup::on($this->getDb())
            ->filter(Filter::equal('user.id', $this->user->id));
        $this->applyRestrictions($groups);

        $list = $this->createEmbeddedList($groups, $this->translate('Groups'), [
            'usergroup.display_name' => $this->translate('Name'),
        ], ['name']);
        if ($list === null) {
            $this->sendMultipartUpdate();

            return;
        }

        $this->addControl(new ObjectHeader($this->user));
        $this->addContent(new UserDetail($this->user, $list));
    }

    /**
     * Show filter suggestions for the contact's groups
     *
     * @return void
     */
    public function completeAction(): void
    {
        $suggestions = (new ObjectSuggestions())
            ->setModel(Usergroup::class)
            ->onlyWithCustomVarSources(['usergroup'])
            ->setBaseFilter(Filter::equal('user.id', $this->user->id))
            ->forRequest($this->getServerRequest());

        $this->getDocument()->add($suggestions);
    }

    /**
     * Show the filter editor for the contact's groups
     *
     * @return void
     */
    public function searchEditorAction(): void
    {
        $editor = $this->createSearchEditor(Usergroup::on($this->getDb()), [
            LimitControl::DEFAULT_LIMIT_PARAM,
            SortControl::DEFAULT_SORT_PARAM,
            'name',
        ]);

        $this->getDocument()->add($editor);
        $this->setTitle($this->translate('Adjust Filter'));
    }
}
