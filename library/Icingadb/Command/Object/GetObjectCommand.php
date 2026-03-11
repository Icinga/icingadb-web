<?php

// SPDX-FileCopyrightText: 2021 Icinga GmbH <https://icinga.com>
// SPDX-License-Identifier: GPL-3.0-or-later

namespace Icinga\Module\Icingadb\Command\Object;

class GetObjectCommand extends ObjectsCommand
{
    /** @var array */
    protected $attributes;

    /**
     * Get the attributes to query
     *
     * @return ?array
     */
    public function getAttributes()
    {
        return $this->attributes;
    }

    /**
     * Set the attributes to query
     *
     * @param array $attributes
     *
     * @return $this
     */
    public function setAttributes(array $attributes): self
    {
        $this->attributes = $attributes;

        return $this;
    }
}
