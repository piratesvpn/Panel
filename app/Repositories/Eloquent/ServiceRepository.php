<?php
/*
 * Pterodactyl - Panel
 * Copyright (c) 2015 - 2017 Dane Everitt <dane@daneeveritt.com>.
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in all
 * copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
 * SOFTWARE.
 */

namespace Pterodactyl\Repositories\Eloquent;

use Webmozart\Assert\Assert;
use Pterodactyl\Models\Service;
use Pterodactyl\Exceptions\Repository\RecordNotFoundException;
use Pterodactyl\Contracts\Repository\ServiceRepositoryInterface;

class ServiceRepository extends EloquentRepository implements ServiceRepositoryInterface
{
    /**
     * {@inheritdoc}
     */
    public function model()
    {
        return Service::class;
    }

    /**
     * {@inheritdoc}
     */
    public function getWithOptions($id = null)
    {
        Assert::nullOrNumeric($id, 'First argument passed to getWithOptions must be null or numeric, received %s.');

        $instance = $this->getBuilder()->with('options.packs', 'options.variables');

        if (! is_null($id)) {
            $instance = $instance->find($id, $this->getColumns());
            if (! $instance) {
                throw new RecordNotFoundException();
            }

            return $instance;
        }

        return $instance->get($this->getColumns());
    }

    /**
     * {@inheritdoc}
     */
    public function getWithOptionServers($id)
    {
        Assert::numeric($id, 'First argument passed to getWithOptionServers must be numeric, received %s.');

        $instance = $this->getBuilder()->with('options.servers')->find($id, $this->getColumns());
        if (! $instance) {
            throw new RecordNotFoundException();
        }

        return $instance;
    }
}
