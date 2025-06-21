jQuery(document).ready(function($){
  var container = $('.wvt-container');
  var loadMoreBtn = $('#wvt-load-more');

  function loadVideos(offset, category, append = true){
    console.log('Loading videos, category:', category, 'offset:', offset);
    $.ajax({
      url: wvt_ajax.ajax_url,
      type: 'POST',
      data: {
        action: 'load_more_videos',
        offset: offset,
        category: category,
        nonce: wvt_ajax.nonce
      },
      beforeSend: function() {
        loadMoreBtn.prop('disabled', true).find('.loading-spinner').show();
      },
      success: function(response) {
        if(response.html) {
          if(append) {
            container.find('.wvt-video-grid').append(response.html);
          } else {
            container.find('.wvt-video-grid').html(response.html);
          }

          var newOffset = offset + container.data('load-more-count');
          loadMoreBtn.data('offset', newOffset);

          if(response.has_more) {
            loadMoreBtn.show().prop('disabled', false).find('.loading-spinner').hide();
          } else {
            loadMoreBtn.hide();
          }
        } else {
          if(!append) {
            container.find('.wvt-video-grid').html('<p>No videos found in this category.</p>');
          }
          loadMoreBtn.hide();
        }
      }
    });
  }

  
  loadMoreBtn.data('offset', container.data('initial-count'));

 
  loadMoreBtn.on('click', function() {
    var offset = loadMoreBtn.data('offset') || container.data('initial-count');
  
    var category = container.attr('data-category') || 'all';

    loadVideos(offset, category, true);
  });

  
  $('.wvt-tabs .tab-button').on('click', function(){
    var button = $(this);
    var category = button.data('category');

    button.siblings().removeClass('active');
    button.addClass('active');

    
    container.attr('data-category', category);
    loadMoreBtn.data('offset', 0); 

    loadVideos(0, category, false); 
  });

 
  var initialCategory = $('.wvt-tabs .tab-button.active').data('category') || 'all';
  container.attr('data-category', initialCategory);
});
