$(document).ready
(
    function()
    {
        if( $('div [rel=phimagebuilder] > img').length && (window.defileImagePreview === undefined))
        {
            var phImageBuilder = new phImageBuilderWidget();
            phImageBuilder.init();
            window.defileImagePreview = true;
        }
    }
)

/*******************************************************************************
 * Copyright (c) 2011 Archer
 *
 * ImgAreaSelect based GUI for ImageBuilderWidget
 *
 * @package CMS
 *
 ******************************************************************************
 */

var phImageBuilderWidget = function()
{
    // Instance of imgAreaSelect plugin
    this.instance = null;

    // Alias for this pointer
    var self = this;

    // Максимальный размер превью
    this.thumbMaxWidth = 1000;

    this.workSide = '';

    this.widget = null;

    // Current selected thumbnail
    this.curThumb = null;

    // Zoom value for current thumb and big image
    this.curZoom = 1;

    this.scale = 1;

    // Original width and height
    var realImageSize = {};

    this.init = function()
    {
        self.setDefaultValues();

        // Получаем реальный размер исходного изображения
        var $img = $('div [rel=phimagebuilder] > img');

        $img.load(function()
        {
            var max = $(this).css('max-width');

            $(this).removeAttr("width")
                .removeAttr("height")
                .css({ width: "", height: ""})
                .css('max-width', '10000px');

            $(this).attr( 'realWidth', $(this).width() );
            $(this).attr( 'realHeight', $(this).height() );
            $(this).css('max-width', '410px');
            $(this).css('width', '410px');
        });

        // для тех браузеров, которые подгрузку из кеша не считают загрузкой
        $img.each
        (
            function()
            {
                var src  = $(this).attr('src');
                $(this).attr('src', '');
                $(this).attr('src', src);
            }
        )

        this.initThumbs();

        // instance new imgArea object
        $('.phimagebuilder_thumb_container img').click
        (
            function()
            {
                // Clear current selection if exists
                if( self.curThumb )
                {
                    self.cancelSelection();
                }

                self.setDefaultValues();

                self.curThumb = $(this);

                self.widget   = self.curThumb.parents('.phimagebuilder_widget_c');
                self.original = $(self.widget).find('div[rel=phimagebuilder] > img');
                var container = $(self.widget).find('.phimagebuilder_thumb_container div[thumbIndex='+self.curThumb.attr('thumbIndex')+']');

                realImageSize.width = self.original.attr('realWidth');
                realImageSize.height = self.original.attr('realHeight');

                // Show controls
                container.find('.phimagebuilder_label_apply, .phimagebuilder_label_discard').show();
                container.find('.phimagebuilder_label_change').hide();

                // Fix thumb container size
                container.find('.phimagebuilder_label_border').css('width', self.curThumb.width() + 'px');
                container.find('.phimagebuilder_label_border').css('height', self.curThumb.height() + 'px');

                var selectedZone = {};
                var aspectRatio = self.original.width() / self.original.height();
                var thumbAspectRatio = self.curThumb.width() / self.curThumb.height();

                self.curZoom  = 1; // Отношение превью к изображению
                self.realZoom = 1; // Отношение превью к оригиналу изображения
                self.aspectK  = 1; // Относительное увеличение соотношения

                // selected area size
                selectedZone.width = self.curThumb.width();
                selectedZone.height = self.curThumb.height();

                // Scale selected area
                if( aspectRatio > 1 )
                {
                    //horizontal
                    if( aspectRatio > thumbAspectRatio )
                    {
                        self.realZoom = realImageSize.height / self.curThumb.height();
                        self.curZoom  = self.original.height() / self.curThumb.height();

                        self.workSide = 'horizontal';
                        self.curThumb.css( 'width', 'auto' );
                        self.curThumb.css( 'height', self.curThumb.height() + 'px' );
                    }
                    else // zoom selected area
                    {
                        self.realZoom = realImageSize.width / self.curThumb.width();
                        self.curZoom  = self.original.width() / self.curThumb.width();

                        self.workSide = 'vertical';
                        self.curThumb.css( 'width', self.curThumb.width() + 'px' );
                        self.curThumb.css( 'height', 'auto' );
                    }
                }
                else
                {
                    //vertical
                    self.realZoom = realImageSize.width / self.curThumb.width();
                    self.curZoom  = self.original.width() / self.curThumb.width();

                    if( aspectRatio < thumbAspectRatio )
                    {
                        self.workSide = 'vertical';
                        self.curThumb.css( 'width', self.curThumb.width() + 'px' );
                        self.curThumb.css( 'height', 'auto' );
                    }
                    else // zoom selected area
                    {
                        self.workSide = 'horizontal';
                        self.aspectK = aspectRatio / thumbAspectRatio;
                        selectedZone.width = self.curThumb.width() / self.aspectK;
                        selectedZone.height = self.curThumb.height() / self.aspectK;
                        self.curThumb.css( 'width', 'auto' );
                        self.curThumb.css( 'height', self.curThumb.height() + 'px' );
                    }
                }

                // Attach widget
                var maxWidth = parseInt( selectedZone.width * self.curZoom );
                var maxHeight = parseInt( selectedZone.height * self.curZoom );
                self.instance = $(self.widget).find('div[rel=phimagebuilder] > img').imgAreaSelect
                (
                    {
                        parent: $(self.widget).find('div[rel=phimagebuilder]'),
                        handles: true,
                        instance: true,   // enable api functions
                        show: true,       // show area
                        fadeSpeed: 200,
                        resizable: true,
                        maxWidth: maxWidth,
                        maxHeight: maxHeight,
                        aspectRatio: maxWidth + ":" + maxHeight,
                        x1: 0,
                        y1: 0,
                        x2: maxWidth,     // thumb like area
                        y2: maxHeight,    // thumb like area
                        onSelectChange: self.onMoveSelection
                    }
                );

                // Set source image as thumb for crop preview
                self.curThumb.attr( 'source', self.curThumb.attr('src') );
                self.curThumb.attr( 'src', self.original.attr('src') );
            }
        );

        $('.phimagebuilder_label_change').click(
            function()
            {
                $(this).parent().find('img').click();
            }
        );


        // Discard selection
        $('.phimagebuilder_label_discard').click
        (
            function()
            {
                self.cancelSelection();
            }
        );

        // Apply selection
        $('.phimagebuilder_label_apply').click
        (
            function()
            {
                self.saveSelection();
            }
        );
    }

    /**
     * Move or resize selected area
     */
    this.onMoveSelection = function( img, selection )
    {
        if( self.workSide == 'horizontal' )
        {
            self.scale = self.original.height() / self.instance.getSelection().height;

            var newHeight = self.curThumb.parent().height() * self.scale;
            self.curThumb.height( newHeight );
        }
        if( self.workSide == 'vertical' )
        {
            self.scale = self.original.width() / self.instance.getSelection().width;

            var newWidth = self.curThumb.parent().width() * self.scale;
            self.curThumb.width( newWidth );
        }

        self.curThumb.css( 'margin-left', (-1)*(selection.x1 / (self.curZoom / (self.scale * self.aspectK))) + 'px' );
        self.curThumb.css( 'margin-top', (-1)*(selection.y1 / (self.curZoom / (self.scale * self.aspectK))) + 'px' );
    }

    this.saveSelection = function()
    {
        var selection   = self.instance.getSelection();
        var name        = self.curThumb.attr('thumbName');
        var action      = self.widget.attr('callback'); // Callback URL
        var fieldName   = self.widget.find('input').attr('name');

        var sizes = $('div[class="phimagebuilder_label_size"]').text();
        var thumbSizeX = sizes.slice(0, sizes.indexOf('x'));

        var formPost =
            {
                field: fieldName,
                filepath: self.original.attr('filepath'),
                name: name,
                x: selection.x1 / self.curZoom, // Переводим смещение рамки в координаты превью
                y: selection.y1 / self.curZoom,
                thumbRation: (selection.x2 - selection.x1) / thumbSizeX,
                curZoom: self.curZoom,
                realZoom: self.realZoom,
            };

        self.curThumb.parent().addClass('waiting');

        $.post
        (
            action,
            formPost,
            function()
            {
                var temp = self.curThumb;
                self.cancelSelection();
                temp.parent().removeClass('waiting');

                self.setDefaultValues();
            }
        );
    }

    this.cancelSelection = function()
    {
        $('.phimagebuilder_label_discard').hide();
        $('.phimagebuilder_label_apply').hide();
        $('.phimagebuilder_label_change').show();

        self.curThumb.css( 'margin-left', 0 + 'px' );
        self.curThumb.css( 'margin-top', 0 + 'px' );

        this.initThumbs();

        if( self.curThumb )
        {
            self.curThumb.attr( 'src', self.curThumb.attr('source') + '?rand=' + Math.random() );
        }
        if( self.instance )
        {
            self.instance.cancelSelection();
        }

        this.setDefaultValues();
    }

    this.setDefaultValues = function()
    {
        self.widget   = null;
        self.original = null;
        self.curThumb = null;
        self.instance = null;
        self.workSide = '';
        self.curZoom  = 1; // Отношение превью к изображению
        self.realZoom = 1; // Отношение превью к оригиналу изображения
        self.aspectK  = 1; // Относительное увеличение соотношения
        self.scale    = 1;

        realImageSize = {};
    }

    this.initThumbs = function()
    {
        $('.phimagebuilder_label_border').each
        (
            function()
            {
                var width = $(this).find('img').attr('realWidth');
                var height = $(this).find('img').attr('realHeight');
                var k = (width > self.thumbMaxWidth) ? width / self.thumbMaxWidth : 1;

                $(this).find('img').css( 'max-width', '10000px' );
                $(this).find('img').css( 'max-height', '10000px' );
                $(this).find('img').width( width / k );
                $(this).find('img').height( height / k );
                $(this).width( width / k );
                $(this).height( height / k );
                $(this).parent().width( (width / k)+10 );
                $(this).parent().height( (height / k)+10 );
            }
        );

        var containers = $('.phimagebuilder_buttons');
        containers.each ( function(){
            $(this).find('.phimagebuilder_label_apply, .phimagebuilder_label_discard').css("width",(parseInt($(this).css("width"))-10)/2-12+"px");
        });
    }
}
