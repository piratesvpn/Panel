<?php
/**
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

namespace Pterodactyl\Http\Controllers\Admin;

use Illuminate\Http\Request;
use Pterodactyl\Models\Pack;
use Prologue\Alerts\AlertsMessageBag;
use Pterodactyl\Http\Controllers\Controller;
use Pterodactyl\Services\Packs\ExportPackService;
use Pterodactyl\Services\Packs\PackUpdateService;
use Pterodactyl\Services\Packs\PackCreationService;
use Pterodactyl\Services\Packs\PackDeletionService;
use Pterodactyl\Http\Requests\Admin\PackFormRequest;
use Pterodactyl\Services\Packs\TemplateUploadService;
use Pterodactyl\Contracts\Repository\PackRepositoryInterface;
use Illuminate\Contracts\Config\Repository as ConfigRepository;
use Pterodactyl\Contracts\Repository\ServiceRepositoryInterface;

class PackController extends Controller
{
    /**
     * @var \Prologue\Alerts\AlertsMessageBag
     */
    protected $alert;

    /**
     * @var \Illuminate\Contracts\Config\Repository
     */
    protected $config;

    /**
     * @var \Pterodactyl\Services\Packs\PackCreationService
     */
    protected $creationService;

    /**
     * @var \Pterodactyl\Services\Packs\PackDeletionService
     */
    protected $deletionService;

    /**
     * @var \Pterodactyl\Services\Packs\ExportPackService
     */
    protected $exportService;

    /**
     * @var \Pterodactyl\Contracts\Repository\PackRepositoryInterface
     */
    protected $repository;

    /**
     * @var \Pterodactyl\Services\Packs\PackUpdateService
     */
    protected $updateService;

    /**
     * @var \Pterodactyl\Contracts\Repository\ServiceRepositoryInterface
     */
    protected $serviceRepository;

    /**
     * @var \Pterodactyl\Services\Packs\TemplateUploadService
     */
    protected $templateUploadService;

    /**
     * PackController constructor.
     *
     * @param \Prologue\Alerts\AlertsMessageBag                            $alert
     * @param \Illuminate\Contracts\Config\Repository                      $config
     * @param \Pterodactyl\Services\Packs\ExportPackService                $exportService
     * @param \Pterodactyl\Services\Packs\PackCreationService              $creationService
     * @param \Pterodactyl\Services\Packs\PackDeletionService              $deletionService
     * @param \Pterodactyl\Contracts\Repository\PackRepositoryInterface    $repository
     * @param \Pterodactyl\Services\Packs\PackUpdateService                $updateService
     * @param \Pterodactyl\Contracts\Repository\ServiceRepositoryInterface $serviceRepository
     * @param \Pterodactyl\Services\Packs\TemplateUploadService            $templateUploadService
     */
    public function __construct(
        AlertsMessageBag $alert,
        ConfigRepository $config,
        ExportPackService $exportService,
        PackCreationService $creationService,
        PackDeletionService $deletionService,
        PackRepositoryInterface $repository,
        PackUpdateService $updateService,
        ServiceRepositoryInterface $serviceRepository,
        TemplateUploadService $templateUploadService
    ) {
        $this->alert = $alert;
        $this->config = $config;
        $this->creationService = $creationService;
        $this->deletionService = $deletionService;
        $this->exportService = $exportService;
        $this->repository = $repository;
        $this->updateService = $updateService;
        $this->serviceRepository = $serviceRepository;
        $this->templateUploadService = $templateUploadService;
    }

    /**
     * Display listing of all packs on the system.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\View\View
     */
    public function index(Request $request)
    {
        return view('admin.packs.index', [
            'packs' => $this->repository->search($request->input('query'))->paginateWithOptionAndServerCount(
                $this->config->get('pterodactyl.paginate.admin.packs')
            ),
        ]);
    }

    /**
     * Display new pack creation form.
     *
     * @return \Illuminate\View\View
     */
    public function create()
    {
        return view('admin.packs.new', [
            'services' => $this->serviceRepository->getWithOptions(),
        ]);
    }

    /**
     * Display new pack creation modal for use with template upload.
     *
     * @return \Illuminate\View\View
     */
    public function newTemplate()
    {
        return view('admin.packs.modal', [
            'services' => $this->serviceRepository->getWithOptions(),
        ]);
    }

    /**
     * Handle create pack request and route user to location.
     *
     * @param \Pterodactyl\Http\Requests\Admin\PackFormRequest $request
     * @return \Illuminate\View\View
     *
     * @throws \Pterodactyl\Exceptions\Model\DataValidationException
     * @throws \Pterodactyl\Exceptions\Service\Pack\InvalidFileMimeTypeException
     * @throws \Pterodactyl\Exceptions\Service\Pack\InvalidFileUploadException
     * @throws \Pterodactyl\Exceptions\Service\Pack\InvalidPackArchiveFormatException
     * @throws \Pterodactyl\Exceptions\Service\Pack\UnreadableZipArchiveException
     * @throws \Pterodactyl\Exceptions\Service\Pack\ZipExtractionException
     */
    public function store(PackFormRequest $request)
    {
        if ($request->has('from_template')) {
            $pack = $this->templateUploadService->handle($request->input('option_id'), $request->file('file_upload'));
        } else {
            $pack = $this->creationService->handle($request->normalize(), $request->file('file_upload'));
        }

        $this->alert->success(trans('admin/pack.notices.pack_created'))->flash();

        return redirect()->route('admin.packs.view', $pack->id);
    }

    /**
     * Display pack view template to user.
     *
     * @param int $pack
     * @return \Illuminate\View\View
     */
    public function view($pack)
    {
        return view('admin.packs.view', [
            'pack' => $this->repository->getWithServers($pack),
            'services' => $this->serviceRepository->getWithOptions(),
        ]);
    }

    /**
     * Handle updating or deleting pack information.
     *
     * @param \Pterodactyl\Http\Requests\Admin\PackFormRequest $request
     * @param \Pterodactyl\Models\Pack                         $pack
     * @return \Illuminate\Http\RedirectResponse
     *
     * @throws \Pterodactyl\Exceptions\Model\DataValidationException
     * @throws \Pterodactyl\Exceptions\Repository\RecordNotFoundException
     * @throws \Pterodactyl\Exceptions\Service\HasActiveServersException
     */
    public function update(PackFormRequest $request, Pack $pack)
    {
        $this->updateService->handle($pack, $request->normalize());
        $this->alert->success(trans('admin/pack.notices.pack_updated'))->flash();

        return redirect()->route('admin.packs.view', $pack->id);
    }

    /**
     * Delete a pack if no servers are attached to it currently.
     *
     * @param \Pterodactyl\Models\Pack $pack
     * @return \Illuminate\Http\RedirectResponse
     *
     * @throws \Pterodactyl\Exceptions\Repository\RecordNotFoundException
     * @throws \Pterodactyl\Exceptions\Service\HasActiveServersException
     */
    public function destroy(Pack $pack)
    {
        $this->deletionService->handle($pack->id);
        $this->alert->success(trans('admin/pack.notices.pack_deleted', [
            'name' => $pack->name,
        ]))->flash();

        return redirect()->route('admin.packs');
    }

    /**
     * Creates an archive of the pack and downloads it to the browser.
     *
     * @param \Pterodactyl\Models\Pack $pack
     * @param bool|string              $files
     * @return \Symfony\Component\HttpFoundation\Response
     *
     * @throws \Pterodactyl\Exceptions\Repository\RecordNotFoundException
     * @throws \Pterodactyl\Exceptions\Service\Pack\ZipArchiveCreationException
     */
    public function export(Pack $pack, $files = false)
    {
        $filename = $this->exportService->handle($pack, is_string($files));

        if (is_string($files)) {
            return response()->download($filename, 'pack-' . $pack->name . '.zip')->deleteFileAfterSend(true);
        }

        return response()->download($filename, 'pack-' . $pack->name . '.json', [
            'Content-Type' => 'application/json',
        ])->deleteFileAfterSend(true);
    }
}
