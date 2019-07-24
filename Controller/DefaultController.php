<?php

namespace ITM\ImagePreviewBundle\Controller;

use Liip\ImagineBundle\Imagine\Filter\FilterConfiguration;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Liip\ImagineBundle\Controller\ImagineController;
use Liip\ImagineBundle\Imagine\Cache\Resolver\WebPathResolver;
use Liip\ImagineBundle\Imagine\Cache\CacheManager as LiipCacheManager;
use Liip\ImagineBundle\Imagine\Data\DataManager;
use Liip\ImagineBundle\Imagine\Filter\FilterManager;

class DefaultController extends Controller
{
    private $filterConfiguration;
    private $cacheManager;
    private $dataManager;
    private $filterManager;

    public function __construct(
        FilterConfiguration $filterConfiguration,
        WebPathResolver $pathResolver,
        LiipCacheManager $cacheManager,
        DataManager $dataManager,
        FilterManager $filterManager)
    {
        $this->filterConfiguration = $filterConfiguration;
        $this->pathResolver = $pathResolver;
        $this->cacheManager  = $cacheManager;
        $this->dataManager   = $dataManager;
        $this->filterManager = $filterManager;
    }

    /**
     * @Template()
     */
    public function indexAction(Request $request)
    {
        $filterName = $request->request->get('name');
        $filePath = $request->request->get('filepath');
        $x = $request->request->get('x');
        $y = $request->request->get('y');
        $thumbRation = $request->request->get('thumbRation');
        $realZoom = $request->request->get('realZoom');
        $curZoom = $request->request->get('curZoom');

        $configuration = $this->filterConfiguration->get($filterName);

        // Получаем конечные размеры превью и размеры самого изображения
        $thumbSize = $configuration['filters']['thumbnail']['size'];
        unset($configuration['filters']['thumbnail']);

        $configuration['filters']['crop']['start'] = [$x * $realZoom, $y * $realZoom];
        $coefficientOfSize = $realZoom / $curZoom * $thumbRation;
        $configuration['filters']['crop']['size'] = [(int)$thumbSize[0] * $coefficientOfSize, (int)$thumbSize[1] * $coefficientOfSize];

        $configuration['filters']['relative_resize']['widen'] = $thumbSize[0];

        $this->pathResolver->remove([$filePath], [$filterName]);
        $binary = $this->dataManager->find($filterName, $filePath);

        $filteredBinary = $this->filterManager->applyFilters($binary, $configuration);
        $this->cacheManager->store($filteredBinary, $filePath, $filterName);

        $thumbUrl = $this->pathResolver->resolve($filePath, $filterName);

        return new Response($thumbUrl);
    }
}
