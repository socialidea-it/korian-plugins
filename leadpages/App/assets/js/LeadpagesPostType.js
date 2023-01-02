(function ($) {
    $(document).ready(
        function () {
            function getLeadPages(clear_cache = false) {
                var start = new Date().getTime();
                var action = 'get_pages_dropdown' + (clear_cache ? '_nocache' : '');
                $.ajax({
                    type: 'POST',
                    url: ajax_object.ajax_url,
                    data: {
                        action: action,
                        id: ajax_object.id
                    },
                    beforeSend: function (data) {
                        $('.ui-loading').show();
                    },
                    success: function (response) {
                        var end = new Date().getTime();
                        // console.log('milliseconds passed', end - start);

                        var pageType = $('input[name=leadpages-post-type]:checked').val();
                        var showSlug = !(pageType === 'nf' || pageType === 'fp');
                        $('#leadpage-slug').toggle(showSlug);

                        $('.ui-loading').hide();
                        $('#leadpages_my_selected_page').append(response);
                    },

                    complete: function (response) {
                        var elem = $(response.responseText);
                        $('#leadpages_my_selected_page').trigger('change');

                        // setup select2 on the leadpages dropdown(sets up searchbox etc)
                        $('.leadpage_select_dropdown').select2({
                            templateResult: function (item) {
                                if (!item.element) {
                                    return;
                                }

                                var data = $(item.element).data();
                                var stats = data.published + ' &bull; ';

                                if (data.issplit) {
                                    stats += 'Split Test';

                                } else {
                                    stats += data.views + ' views &bull; '
                                        + data.optins + ' optins</small>';
                                }

                                return $('<div>'
                                    + '<div>' + item.text + '</div>'
                                    + '<small style="color: #bbbbbb">'
                                    + stats + '</div>');
                            },
                            placeholder: 'Select a Leadpage',
                            allowClear: true
                        });

                        $('.sync-leadpages').show();
                    }
                });
            }

            getLeadPages();

            $('#leadpages_my_selected_page').on('select2:open', function () {
                $('.select2-search__field').attr('placeholder', 'Search Your Leadpages');
            });

            var $body = $('body');

            function handleSlug() {
                var slugInputSelector = '.leadpages-slug-input';
                var item = $('#leadpages_my_selected_page option:selected');
                var pageType = $('input[name=leadpages-post-type]:checked').val()
                var showSlug = !(pageType === 'nf' || pageType === 'fp');
                var isEdit = $('.leadpages-edit-wrapper').data('isEdit');

                if (isEdit !== undefined && !isEdit || $(slugInputSelector).val() === '') {
                    $(slugInputSelector).val(item.data('slug'));
                }

                if (!showSlug) {
                    $('.leadpage_slug_error').remove();
                    $(slugInputSelector).val('');
                }

                $('#leadpage-slug').toggle(showSlug);
            }

            function handleCacheLock(isLocked = true) {
                $('#leadpage-cache input[type="radio"]').attr('disabled', isLocked);

                if (isLocked) {
                    $('#cache_this_false').attr('checked', true);
                }
            }

            $body.on('change', '#leadpages_my_selected_page', function () {
                var item = $('option:selected', this);
                var selected_page_name = item.text();
                var isSplit = !!item.data('issplit');
                $('#leadpages_name').val(selected_page_name);

                handleSlug();
                handleCacheLock(isSplit);
            });

            $body.on('change', 'input[name=leadpages-post-type]', function () {
                handleSlug();
            });

            // hide preview button for Leadpages
            $('#preview-action').hide();

            // refresh button for leadpages
            $body.on('click', '.sync-leadpages', function (e) {
                // show loading icons
                $('.sync-leadpages i').hide();

                // remove all old data
                $('#leadpages_my_selected_page').empty();

                // get new leadpages and recreate dropdown
                getLeadPages(true);

                $('.sync-leadpages i').show();
            });

            function resetErrorState() {
                $('#publishing-action .spinner').removeClass('is-active');
                $('.publish-button').removeClass('disabled');
                $('.leadpages_error').remove();
                $('#leadpages_my_selected_page .select2-selection').css('border-color', '#dddddd');
                $('.select_a_leadpage_type h3').css('color', 'initial');
            }

            function addErrorNotice(message) {
                var content = '<div class="error notice leadpages_error"><p>'
                    + message
                    + '</p></div>';

                $('.wrap h1').after(content);
            }

            $body.on('click', '.publish-button', function (e) {
                resetErrorState();
                var error = false;
                var leadpageType = $('[name="leadpages-post-type"]:checked').val();
                var selectedPage = $('[name="leadpages_my_selected_page"]').val();
                var leadpageSlug = $('.leadpages-slug-input').val() || '';

                if (!selectedPage) {
                    e.preventDefault();
                    addErrorNotice('Please select a Leadpage');
                    $('#leadpages_my_selected_page .select2-selection').css('border-color', 'red');
                    error = true;
                }

                if (leadpageType.length === 0) {
                    e.preventDefault();
                    addErrorNotice('Please select a page type');
                    $('.select_a_leadpage_type h3').css('color', 'red');
                    error = true;
                }

                if (leadpageType !== 'fp' && leadpageType !== 'nf') {
                    if (leadpageSlug.length === 0) {
                        e.preventDefault();
                        addErrorNotice('Slug appears to be empty. Please add a slug.');
                        $('.leadpages-slug-input').css('border-color', 'red');
                        error = true;
                    }
                } else {
                    $('.leadpage_slug_error').remove();
                }

                if (error) {
                    return;
                }
            });

            // remove all the unneeded styling from metaboxes
            function removeMetaBoxExpand() {
                $('.postbox .hndle').unbind('click.postboxes');
                $('.postbox .handlediv').remove();
                $('.postbox').removeClass('closed');
                $('.postbox .hndle').remove();
            }
            removeMetaBoxExpand();

            // setting up the Leadpages Post Type Page for redesign
            $('#leadpage-create').removeClass('postbox');
            $('#leadpage-create > div').removeClass('inside');
        }
    );
}(jQuery));
