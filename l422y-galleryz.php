<?php
/**
 * Plugin Name: L422Y Galleryz
 * Description: Plugin for inserting galleries using shortcodes
 * Version: 3.0
 * Author: Larry Williamson / L422Y
 * Author URI: http://l422y.com
 * License: GPL2
 */

function l422y_galleries_enqueue()
{
    wp_enqueue_style('l422y-gallery-styles', plugins_url('/l422y-galleryz.css', __FILE__), array(), '1.0');
    wp_enqueue_script('l422y-gallery-scripts', plugins_url('/l422y-galleryz.js', __FILE__), array(), '1.0', true);
}

function l422y_galleries_post_type()
{
    register_post_type('l422y_gallery',
        array(
            'labels' => array(
                'name' => __('Galleries', 'text_domain'),
                'singular_name' => __('Gallery', 'text_domain'),
                'all_items' => __('All Galleries', 'text_domain'),
                'add_new_item' => __('Add New Gallery', 'text_domain'),
                'add_new' => __('Add New', 'text_domain'),
                'new_item' => __('New Gallery', 'text_domain'),
                'edit_item' => __('Edit Gallery', 'text_domain'),
                'update_item' => __('Update Gallery', 'text_domain'),
                'view_item' => __('View Gallery', 'text_domain'),
                'view_items' => __('View Galleries', 'text_domain'),
                'search_items' => __('Search Gallery', 'text_domain'),
                'featured_image' => __('Featured Image', 'text_domain'),
                'set_featured_image' => __('Set featured image', 'text_domain'),
            ),
            'rewrite' => array('slug' => 'gallery'),
            'public' => true,
            'capability_type' => 'post',
            'has_archive' => true,
            'description' => 'Represents a single slide in the header slideshow.',
            'hierarchical' => false,
            'menu_icon' => 'dashicons-images-alt2',
            'menu_position' => 5,
            'show_in_admin_bar' => false,
            'show_in_nav_menus' => true,
            'show_ui' => true,
            'supports' => array(
                'title', 'editor',
                'author', 'thumbnail',
                'custom-fields',
                'permalink',
                'revisions',
            )

        )
    );

    $l422y_gallery_index = 0;
    add_shortcode('galleryz', function ($atts, $content = "") {
        global $l422y_gallery_index;
        $ag_images = [];
        ob_start();
        $l422y_gallery_index++;
        $gallery_id = "l422y_gallery_{$l422y_gallery_index}";
        $fitmode = $atts['fit'] ?: 'contain';
        if (isset($atts['name'])):
            $gallery_name = $atts['name'];
            if ($post = get_page_by_path($gallery_name, OBJECT, 'l422y_gallery')):
                $fields = get_fields($post->ID);
                foreach ($fields['l4g_images'] as $i):

                    $val = $i['sizes']['medium_large'] ?: $i['url'];
                    if ($val):
                        $ag_images[] = [
                            'src' => $val,
                            'caption' => $i['caption'],
                            'thumb' => $i['sizes']['small']
                        ];
                    endif;
                endforeach;
            else:
                return;
            endif;
        elseif (isset($atts['ids'])):

            $opts = array(
                'post_type' => 'attachment',
                'numberposts' => 100,
                'post__in' => explode(',', $atts['ids']),
                'orderby' => 'post_date',
                'order' => 'DESC',
            );

            $attachments = get_posts($opts);

            foreach ($attachments as $a):
                $ag_images[] = [
                    'src' => wp_get_attachment_image_url($a->ID, 'medium_large') ?: wp_get_attachment_image_url($a->ID),
                    'caption' => wp_get_attachment_caption($a->ID),
                    'thumb' => wp_get_attachment_thumb_url($a->ID)
                ];
            endforeach;
        endif;
        if (!empty($ag_images)):
            ?>
            <script>
                let images_<?php echo $gallery_id ?>=<?php echo json_encode($ag_images, JSON_UNESCAPED_SLASHES) ?>;
                let currentImage_<?php echo $gallery_id ?> = <?php echo json_encode($ag_images[0], JSON_UNESCAPED_SLASHES) ?>
            </script>

            <div class="l422y-gallery <?php echo !empty($atts['thumbs']) ? 'thumbs' : 'nothumbs' ?> fit-<?php echo $fitmode ?>"
                 id="<?php echo $gallery_id ?>"
                 v-cloak
                 v-scope="{ images_<?php echo $gallery_id ?>, currentImage_<?php echo $gallery_id ?>  }"
                 @vue:mounted="ag_mounted($el,'<?php echo $gallery_id ?>')"
            >
                <div class="preview">
                    <picture>
                        <img :src="currentImage_<?php echo $gallery_id ?>.src" class="bg" alt="">
                        <img :src="currentImage_<?php echo $gallery_id ?>.src" alt=""
                             @click.prevent="if(window.store) { store?.setfullGalleryItems(images_<?php echo $gallery_id ?>) }">
                    </picture>
                    <nav>
                        <i class="prev"
                           @click.prevent="currentImage_<?php echo $gallery_id ?>=ag_prev( images_<?php echo $gallery_id ?>,currentImage_<?php echo $gallery_id ?>)">
                            <img src="<?php echo plugins_url('images/left-svgrepo-com.svg', __FILE__) ?>" alt="">
                        </i>
                        <i class="next"
                           @click.prevent="currentImage_<?php echo $gallery_id ?>=ag_next( images_<?php echo $gallery_id ?>,currentImage_<?php echo $gallery_id ?>)">
                            <img src="<?php echo plugins_url('images/right-svgrepo-com.svg', __FILE__) ?>" alt="">
                        </i>
                    </nav>
                    <div class="lg-caption" v-if="currentImage_<?php echo $gallery_id ?>?.caption">
                        {{currentImage_<?php echo $gallery_id ?>?.caption}}
                    </div>
                    <div class="l422y-gallery-indicators">
                        <div class="lgi-pos">
                            {{ images_<?php echo $gallery_id ?>.indexOf(currentImage_<?php echo $gallery_id ?>) > -1 ?
                            images_<?php echo $gallery_id ?>.indexOf(currentImage_<?php echo $gallery_id ?>) +1 : 1 }} /
                            {{ images_<?php echo $gallery_id ?>.length}}
                        </div>
                        <div class="lgi-viewall"
                             @click.prevent="store.setfullGalleryItems( images_<?php echo $gallery_id ?>)">
                            View All
                        </div>
                    </div>
                </div>

                <div class="l422y-gallery-thumbs">
                    <div class="l422y-gallery-thumbs-container" id="<?php echo $gallery_id ?>_thumbs">
                        <picture v-for='image of  images_<?php echo $gallery_id ?>'
                                 :class="currentImage_<?php echo $gallery_id ?>.src === image.src ? 'active' :'inactive'"
                        >
                            <img
                                    @click="currentImage_<?php echo $gallery_id ?>=image"
                                    :src="image.thumb"
                                    :alt="image.caption"
                            />
                        </picture>
                    </div>
                </div>
                <div class="scrubber" id="<?php echo $gallery_id ?>_scrubber">
                    <div class="grip"
                         id="<?php echo $gallery_id ?>_grip"
                         @mousedown="ag_startDrag($event,'<?php echo $gallery_id ?>')"
                         @mouseup="ag_endDrag($event,'<?php echo $gallery_id ?>')"
                    ></div>
                </div>
            </div>

        <?php
        endif;
        $code = ob_get_clean();
        wp_reset_query();
        return $code;
    });

}

add_action('acf/init', function () {

//    if (function_exists('acf_add_local_field_group')):

    acf_add_local_field_group(array(
        'menu_order' => -1,
        'position' => 'acf_after_title',
        'key' => 'gallery_group',
        'title' => 'Gallery Images',
        'fields' => array(
            array(
                'key' => 'l4g_images',
//                'label' => 'Post Format',
                'name' => 'l4g_images',
                'type' => 'gallery',
                'layout' => 'horizontal',
            )
        ),
        'location' => array(
            array(
                array(
                    'param' => 'post_type',
                    'operator' => '==',
                    'value' => 'l422y_gallery',
                ),
            ),
        ),
    ));

//    endif;
});


function l422y_galleries_footer()
{
    ?>
    <script>
        let fgPrev = (fullGalleryItems, fullGalleryCurrentItem) => {
            let fgci = fullGalleryCurrentItem || fullGalleryItems[0]
            let currentIndex = fullGalleryItems.findIndex((el) => el.src === fgci.src)
            return fullGalleryItems[currentIndex - 1] || fullGalleryItems[fullGalleryItems.length - 1]
        }
        let fgNext = (fullGalleryItems, fullGalleryCurrentItem) => {
            let fgci = fullGalleryCurrentItem || fullGalleryItems[0]
            let currentIndex = fullGalleryItems.findIndex((el) => el.src === fgci.src)
            return fullGalleryItems[currentIndex + 1] || fullGalleryItems[0]
        }
    </script>
    <div v-show="store?.fullGalleryItems?.length>0" :class="store?.fullGalleryItems?.length>0?'active':''"
         class="l422y-gallery-full">
        <div class="lg-fg-close" @click="store?.unsetfullGalleryItems()">&times;</div>
        <div class="preview">
            <picture v-if="store.fullGalleryCurrentItem">
                <img :src="store.fullGalleryCurrentItem?.src" alt="" class="bg">
                <img :src="store.fullGalleryCurrentItem?.src" alt=""/>
            </picture>
            <picture v-else>
                <img :src="store.fullGalleryItems[0]?.src" alt="" class="bg">
                <img :src="store.fullGalleryItems[0]?.src" alt="">
            </picture>

            <div class="lg-caption" v-if="store.fullGalleryCurrentItem?.caption"
                 v-html="store.fullGalleryCurrentItem?.caption"></div>
            <nav>
                <i class="prev"
                   @click="store.fullGalleryCurrentItem=fgPrev(store.fullGalleryItems,store.fullGalleryCurrentItem)">
                    <img src="<?php echo plugins_url('images/left-svgrepo-com.svg', __FILE__) ?>" alt="">
                </i>
                <i class="next"
                   @click="store.fullGalleryCurrentItem=fgNext(store.fullGalleryItems,store.fullGalleryCurrentItem)">
                    <img src="<?php echo plugins_url('images/right-svgrepo-com.svg', __FILE__) ?>" alt="">
                </i>
            </nav>
        </div>
        <div class="l422y-gallery-thumbs">
            <div class="l422y-gallery-thumbs-container">
                <img
                        v-for='image of store.fullGalleryItems'
                        @click="store.fullGalleryCurrentItem=image"
                        :src="image.thumb"
                        :class="store.fullGalleryCurrentItem.src === image.src ? 'active' :''"
                        :alt="image.caption"
                />
            </div>
        </div>
    </div>

    <script type="module">
        document.querySelector('body').setAttribute('v-scope', '')

        import {createApp, reactive} from 'https://unpkg.com/petite-vue?module'

        const store = reactive({
            fullGalleryItems: [],
            fullGalleryCurrentItem: false,
            setfullGalleryItems(items) {
                this.fullGalleryItems = items
                this.fullGalleryCurrentItem = items[0]
            },
            unsetfullGalleryItems() {
                this.fullGalleryItems = []
            }
        })
        window.store = store

        document.body.addEventListener('keyup', (ev) => {
            if (ev.key === 'Escape' && store.fullGalleryItems?.length > 0) {
                ev.preventDefault()
                store.unsetfullGalleryItems();
                return false
            }
        })

        createApp({store}).mount('body')
    </script>
    <?php
}

add_action('wp_footer', 'l422y_galleries_footer');
add_action('init', 'l422y_galleries_post_type');
add_action('wp_enqueue_scripts', 'l422y_galleries_enqueue');
