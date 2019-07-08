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

class DefaultController extends Controller
{
    private $filterConfiguration;

    public function __construct(
        FilterConfiguration $filterConfiguration,
        ImagineController $imagemanagerResponse,
        WebPathResolver $pathResolver)
    {
        $this->filterConfiguration = $filterConfiguration;
        $this->imagemanagerResponse = $imagemanagerResponse;
        $this->pathResolver = $pathResolver;
    }

    /**
     * @Template()
     */
    public function indexAction(Request $request)
    {
        $filterName = $request->request->get('name');
        $filePath = $request->request->get('filepath');
        $scale = $request->request->get('scale');
        $x = $request->request->get('x');
        $y = $request->request->get('y');

        $configuration = $this->filterConfiguration->get($filterName);

        // Получаем конечные размеры превью и размеры самого изображения
        $thumbSize = $configuration['filters']['thumbnail']['size'];

        unset($configuration['filters']['thumbnail']);
        // Вписываем изображение в область превью
        $side = ($thumbSize[1] > $thumbSize[0]) ? 'widen' : 'heighten';
        $configuration['filters']['relative_resize'][$side] = max($thumbSize)*$scale;

        // Обрезам лишнее с указанным смещением
        $configuration['filters']['crop']['size'] = [$thumbSize[0], $thumbSize[1]];
        $configuration['filters']['crop']['start'] = [$x*$scale, $y*$scale];
        $configuration['filters']['upscale']['min'] = $thumbSize;

        $this->filterConfiguration->set($filterName, $configuration);

        $this->pathResolver->remove([$filePath], [$filterName]);

        $this->imagemanagerResponse->filterAction($request, $filePath, $filterName);

        $thumbUrl = $this->pathResolver->resolve($filePath, $filterName);

        return new Response($thumbUrl);
    }
}
