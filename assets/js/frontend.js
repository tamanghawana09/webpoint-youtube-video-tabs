jQuery(document).ready(function($) {
    let currentOffset = 0;
    let currentCategory = 'all';
    let isLoading = false;
    
    const container = $('.wvt-container');
    const videoGrid = $('.wvt-video-grid');
    const loadMoreBtn = $('#wvt-load-more');
    const loadingSpinner = $('.loading-spinner');
    
    // Get initial settings from container
    const initialCount = parseInt(container.data('initial-count')) || 6;
    const loadMoreCount = parseInt(container.data('load-more-count')) || 6;
    
    // Initialize offset with initial count
    currentOffset = initialCount;
    
    // Tab button click handler
    $('.tab-button').on('click', function(e) {
        e.preventDefault();
        
        if (isLoading) return;
        
        const $this = $(this);
        const newCategory = $this.data('category') || 'all';
        
        // Update active state
        $('.tab-button').removeClass('active');
        $this.addClass('active');
        
        // If same category, do nothing
        if (newCategory === currentCategory) return;
        
        // Update current category and reset offset
        currentCategory = newCategory;
        currentOffset = 0;
        
        // Show loading state
        videoGrid.addClass('loading');
        loadMoreBtn.hide();
        
        // Load filtered content
        loadVideos(true); // true = replace content
    });
    
    // Load more button click handler
    loadMoreBtn.on('click', function(e) {
        e.preventDefault();
        
        if (isLoading) return;
        
        loadVideos(false); // false = append content
    });
    
    // Main function to load videos
    function loadVideos(replaceContent = false) {
        if (isLoading) return;
        
        isLoading = true;
        
        // Show loading indicators
        loadingSpinner.show();
        loadMoreBtn.prop('disabled', true);
        
        const ajaxData = {
            action: 'load_more_videos',
            offset: replaceContent ? 0 : currentOffset,
            category: currentCategory,
            posts_per_page: replaceContent ? initialCount : loadMoreCount,
            nonce: wvt_ajax.nonce
        };
        
        console.log('Loading videos with data:', ajaxData);
        
        $.ajax({
            url: wvt_ajax.ajax_url,
            type: 'POST',
            data: ajaxData,
            success: function(response) {
                console.log('AJAX Response:', response);
                
                if (response.success !== false && response.html) {
                    if (replaceContent) {
                        // Replace all content for category filtering
                        videoGrid.html(response.html);
                        currentOffset = initialCount; // Reset to initial count
                    } else {
                        // Append content for load more
                        videoGrid.append(response.html);
                        currentOffset += loadMoreCount; // Add the load more count
                    }
                    
                    // Update load more button visibility
                    if (response.has_more) {
                        loadMoreBtn.show();
                    } else {
                        loadMoreBtn.hide();
                    }
                    
                    // Remove loading states
                    videoGrid.removeClass('loading');
                    
                } else {
                    console.error('No content received or error in response');
                    if (replaceContent) {
                        videoGrid.html('<p class="no-videos-message">No videos found in this category.</p>');
                    }
                    loadMoreBtn.hide();
                }
            },
            error: function(xhr, status, error) {
                console.error('AJAX Error:', error);
                console.error('Status:', status);
                console.error('Response:', xhr.responseText);
                
                // Show error message
                if (replaceContent) {
                    videoGrid.html('<p class="error-message">Error loading videos. Please try again.</p>');
                } else {
                    // Show temporary error message
                    const errorMsg = $('<p class="error-message">Error loading more videos. Please try again.</p>');
                    videoGrid.append(errorMsg);
                    setTimeout(() => errorMsg.remove(), 3000);
                }
                
                loadMoreBtn.show();
            },
            complete: function() {
                // Reset loading states
                isLoading = false;
                loadingSpinner.hide();
                loadMoreBtn.prop('disabled', false);
                videoGrid.removeClass('loading');
            }
        });
    }
    
    // Video click handler for modal/lightbox (if needed)
    $(document).on('click', '.video-item', function(e) {
        e.preventDefault();
        
        const youtubeUrl = $(this).data('youtube-url');
        const youtubeId = $(this).data('youtube-id');
        
        if (youtubeUrl && youtubeId) {
            // Open video in modal or new tab
            // You can customize this behavior
            window.open(youtubeUrl, '_blank');
        }
    });
    
    // Initialize: Show/hide load more button based on initial content
    function initializeLoadMore() {
        const totalVideos = videoGrid.find('.video-item').length;
        
        // This should be set from PHP or make an initial AJAX call to get total count
        // For now, we'll show the button and let the AJAX response handle it
        if (totalVideos >= initialCount) {
            loadMoreBtn.show();
        } else {
            loadMoreBtn.hide();
        }
    }
    
    // Initialize on page load
    initializeLoadMore();
    
    // Optional: Add smooth scrolling to new content
    function scrollToNewContent() {
        const lastVisibleItem = videoGrid.find('.video-item:visible').last();
        if (lastVisibleItem.length) {
            $('html, body').animate({
                scrollTop: lastVisibleItem.offset().top - 100
            }, 500);
        }
    }
});