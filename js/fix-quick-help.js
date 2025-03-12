document.addEventListener('DOMContentLoaded', function() {
    // Force proper styling for quick help section
    const quickHelpSection = document.querySelector('.quick-help');
    const helpContent = document.querySelector('.help-content');
    const helpList = document.querySelector('.help-content ol');
    
    if (quickHelpSection) {
        // Apply inline styles as a fallback
        quickHelpSection.style.display = 'block';
        quickHelpSection.style.width = '100%';
        quickHelpSection.style.background = 'white';
        quickHelpSection.style.borderRadius = '10px';
        quickHelpSection.style.padding = '2rem';
    }
    
    if (helpContent) {
        helpContent.style.display = 'block';
        helpContent.style.margin = '0 auto';
    }
    
    if (helpList) {
        helpList.style.display = 'block';
        helpList.style.listStyleType = 'decimal';
        helpList.style.paddingLeft = '2rem';
        
        // Fix each list item
        const listItems = helpList.querySelectorAll('li');
        listItems.forEach(item => {
            item.style.display = 'list-item';
            item.style.listStylePosition = 'outside';
            item.style.marginBottom = '0.5rem';
        });
    }
});
