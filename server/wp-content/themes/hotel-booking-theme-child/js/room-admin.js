jQuery(function ($) {
    var frame = null;
    var idsInput = $('#hotel-room-gallery-ids');
    var preview = $('#hotel-room-gallery-preview');

    function renderPreview(attachments) {
        preview.empty();

        attachments.forEach(function (attachment) {
            var url = (attachment.sizes && attachment.sizes.thumbnail && attachment.sizes.thumbnail.url)
                ? attachment.sizes.thumbnail.url
                : attachment.url;

            $('<img>', {
                src: url,
                alt: '',
                css: {
                    width: '100%',
                    height: '72px',
                    objectFit: 'cover',
                    borderRadius: '8px'
                }
            }).appendTo(preview);
        });
    }

    $('#hotel-room-gallery-select').on('click', function (event) {
        event.preventDefault();

        if (frame) {
            frame.open();
            return;
        }

        frame = wp.media({
            title: 'Select Room Gallery Images',
            button: {
                text: 'Use selected images'
            },
            multiple: true,
            library: {
                type: 'image'
            }
        });

        frame.on('select', function () {
            var selection = frame.state().get('selection').toJSON();
            var ids = selection.map(function (item) {
                return item.id;
            });

            idsInput.val(ids.join(','));
            renderPreview(selection);
        });

        frame.open();
    });

    $('#hotel-room-gallery-clear').on('click', function (event) {
        event.preventDefault();
        idsInput.val('');
        preview.empty();
    });
});
