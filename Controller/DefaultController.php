<?php

namespace ITM\ImagePreviewBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class DefaultController extends Controller
{
    /**
     * @Template()
     */
    public function indexAction(Request $request)
    {
        $filterName = $request->get('name');
        $filePath = $request->get('filepath');
        $scale = $request->get('scale');
        $x = $request->get('x');
        $y = $request->get('y');

        $container = $this->container;

        $imagemanagerResponse = $container->get('liip_imagine.controller');
        $pathResolver = $container->get('liip_imagine.cache.resolver.default');
        $filterConfiguration = $container->get('liip_imagine.filter.configuration');

        $configuration = $filterConfiguration->get($filterName);

        // Получаем конечные размеры превью и размеры самого изображения
        $thumbSize = $configuration['filters']['thumbnail']['size'];

        unset($configuration['filters']['thumbnail']);
        // Вписываем изображение в область превью
        $side = ($thumbSize[1] > $thumbSize[0]) ? 'heighten' : 'widen';
        $configuration['filters']['relative_resize'][$side] = max($thumbSize)*$scale;

        // Обрезам лишнее с указанным смещением
        $configuration['filters']['crop']['size'] = [$thumbSize[0], $thumbSize[1]];
        $configuration['filters']['crop']['start'] = [$x*$scale, $y*$scale];
        $configuration['filters']['upscale']['min'] = $thumbSize;

        $filterConfiguration->set($filterName, $configuration);

        $pathResolver->remove([$filePath], [$filterName]);

        $imagemanagerResponse->filterAction($request, $filePath, $filterName);

        $thumbUrl = $pathResolver->resolve($filePath, $filterName);

        return new Response($thumbUrl);
    }
}
