// Utility functions
const showToast = (message, type = 'info') => {
    const toast = document.createElement('div');
    toast.className = `toast toast-${type}`;
    toast.textContent = message;
    document.querySelector('.toast-container').appendChild(toast);
    setTimeout(() => toast.classList.add('show'), 100);
    setTimeout(() => {
        toast.classList.remove('show');
        setTimeout(() => toast.remove(), 300);
    }, 3000);
};

const formatNumber = (num) => {
    if (num >= 1000000) {
        return (num / 1000000).toFixed(1) + 'M';
    } else if (num >= 1000) {
        return (num / 1000).toFixed(1) + 'k';
    }
    return num.toString();
};

document.addEventListener('DOMContentLoaded', function() {
    // Profile dropdown functionality
    const profile = document.querySelector('.profile');
    const profileDropdown = document.querySelector('.profile-dropdown');
    
    if (profile && profileDropdown) {
        profile.addEventListener('click', (e) => {
            e.stopPropagation();
            profileDropdown.classList.toggle('show');
        });

        document.addEventListener('click', () => {
            profileDropdown.classList.remove('show');
        });
    }

    // Handle back button
    const backBtn = document.querySelector('.back-btn');
    backBtn.addEventListener('click', () => {
        window.history.back();
    });

    // New comment functionality
    const newCommentBtn = document.querySelector('.new-comment-btn');
    const newCommentForm = document.querySelector('.new-comment-form');
    
    if (newCommentBtn && newCommentForm) {
        const commentTextarea = newCommentForm.querySelector('textarea');
        const submitCommentBtn = newCommentForm.querySelector('.submit-comment');
        const cancelCommentBtn = newCommentForm.querySelector('.cancel-comment');

        newCommentBtn.addEventListener('click', () => {
            newCommentForm.classList.remove('hidden');
            commentTextarea.focus();
        });

        cancelCommentBtn.addEventListener('click', () => {
            newCommentForm.classList.add('hidden');
            commentTextarea.value = '';
        });

        submitCommentBtn.addEventListener('click', () => {
            const commentText = commentTextarea.value.trim();
            if (commentText) {
                addNewComment(commentText);
                newCommentForm.classList.add('hidden');
                commentTextarea.value = '';
                showToast('Comment posted successfully!', 'success');
            } else {
                showToast('Please write something before posting.', 'error');
            }
        });
    }

    // Handle love button for post
    const postLoveButton = document.querySelector('.post-actions .love-btn');
    if (postLoveButton) {
        postLoveButton.addEventListener('click', function() {
            const loveCount = this.parentElement.querySelector('.love-count');
            let count = parseInt(loveCount.textContent);
            
            if (!this.classList.contains('active')) {
                count += 1;
                this.classList.add('active');
            } else {
                count -= 1;
                this.classList.remove('active');
            }
            
            loveCount.textContent = formatNumber(count);
            showToast('Thanks for your feedback!', 'success');
        });
    }

    // Share functionality
    const shareButtons = document.querySelectorAll('.share-btn');
    shareButtons.forEach(button => {
        button.addEventListener('click', async () => {
            const commentText = button.closest('.comment').querySelector('.comment-text').textContent;
            try {
                await navigator.share({
                    title: 'Shared Comment',
                    text: commentText,
                    url: window.location.href
                });
                showToast('Comment shared successfully!', 'success');
            } catch (err) {
                navigator.clipboard.writeText(commentText);
                showToast('Comment copied to clipboard!', 'success');
            }
        });
    });

    // Report Modal Functionality
    const reportButtons = document.querySelectorAll('.report-btn');
    const postReportBtn = document.querySelector('.post-report-btn');
    const reportModal = document.getElementById('reportModal');
    const closeModal = reportModal.querySelector('.close-modal');
    const cancelBtn = reportModal.querySelector('.cancel-btn');
    const reportForm = document.getElementById('reportForm');
    let currentReportTarget = null;

    function openModal(isPost = false) {
        reportModal.classList.add('show');
        document.body.style.overflow = 'hidden';
        currentReportTarget = isPost ? 'post' : 'comment';
    }

    function closeModalFunc() {
        reportModal.classList.remove('show');
        document.body.style.overflow = '';
        reportForm.reset();
        currentReportTarget = null;
    }

    reportButtons.forEach(button => {
        button.addEventListener('click', () => {
            openModal(false);
        });
    });

    if (postReportBtn) {
        postReportBtn.addEventListener('click', () => {
            openModal(true);
        });
    }

    closeModal.addEventListener('click', closeModalFunc);
    cancelBtn.addEventListener('click', closeModalFunc);

    reportModal.addEventListener('click', (e) => {
        if (e.target === reportModal) {
            closeModalFunc();
        }
    });

    reportForm.addEventListener('submit', (e) => {
        e.preventDefault();
        const formData = new FormData(reportForm);
        const violationType = formData.get('violation');
        const additionalInfo = formData.get('additional-info');

        // Here you would typically send this data to your backend
        console.log('Report submitted:', {
            type: currentReportTarget,
            violationType,
            additionalInfo
        });

        showToast(`${currentReportTarget === 'post' ? 'Post' : 'Comment'} reported successfully`, 'success');
        closeModalFunc();
    });

    // Author link click handler
    const authorLink = document.querySelector('.author-link');
    if (authorLink) {
        authorLink.addEventListener('click', (e) => {
            e.preventDefault();
            showToast('Viewing Alex Thompson\'s profile...', 'info');
            // Here you can add code to navigate to the author's profile page
            // or show a profile modal when backend is implemented
        });
    }

    function addNewComment(text) {
        const commentsSection = document.querySelector('.comments-section');
        const newComment = document.createElement('div');
        newComment.className = 'comment';
        
        newComment.innerHTML = `
            <div class="comment-header">
                <img src="https://api.dicebear.com/6.x/avataaars/svg?seed=John" alt="User Avatar" class="user-avatar">
                <div class="comment-info">
                    <div class="user-info">
                        <span class="username">John Doe</span>
                        <span class="user-badge" title="You">👤</span>
                    </div>
                    <span class="comment-time">just now</span>
                </div>
            </div>
            <p class="comment-text">${text}</p>
            <div class="comment-actions">
                <button class="share-btn"><i class="fas fa-share"></i> Share</button>
                <button class="report-btn"><i class="fas fa-flag"></i> Report</button>
            </div>
        `;
        
        commentsSection.insertBefore(newComment, commentsSection.firstChild);
        setupNewCommentInteractions(newComment);
    }

    function setupNewCommentInteractions(comment) {
        const shareBtn = comment.querySelector('.share-btn');
        const reportBtn = comment.querySelector('.report-btn');

        shareBtn.addEventListener('click', async () => {
            const commentText = comment.querySelector('.comment-text').textContent;
            try {
                await navigator.share({
                    title: 'Shared Comment',
                    text: commentText,
                    url: window.location.href
                });
                showToast('Comment shared successfully!', 'success');
            } catch (err) {
                navigator.clipboard.writeText(commentText);
                showToast('Comment copied to clipboard!', 'success');
            }
        });

        reportBtn.addEventListener('click', () => {
            openModal();
        });
    }

    // Initialize all existing comments
    document.querySelectorAll('.comment').forEach(comment => {
        setupNewCommentInteractions(comment);
    });
});
