document.addEventListener('DOMContentLoaded', function() {
    // More aggressive styling for the quick help section
    const quickHelpSection = document.querySelector('.quick-help');
    const helpContent = document.querySelector('.help-content');
    const helpList = document.querySelector('.help-content ol');
    
    if (quickHelpSection) {
        // Apply more detailed inline styles
        quickHelpSection.style.cssText = `
            display: block !important;
            width: 100% !important;
            background: white !important;
            border-radius: 10px !important;
            padding: 2.5rem !important;
            margin: 3rem auto !important;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.05) !important;
            transition: transform 0.3s ease, box-shadow 0.3s ease !important;
            border: 1px solid rgba(0, 0, 0, 0.05) !important;
            overflow: visible !important;
            max-width: 1200px !important;
        `;
        
        // Add hover effect event listeners
        quickHelpSection.addEventListener('mouseenter', function() {
            this.style.boxShadow = '0 8px 16px rgba(0, 0, 0, 0.1)';
            this.style.transform = 'translateY(-2px)';
        });
        
        quickHelpSection.addEventListener('mouseleave', function() {
            this.style.boxShadow = '0 4px 8px rgba(0, 0, 0, 0.05)';
            this.style.transform = 'translateY(0)';
        });
    }
    
    if (helpContent) {
        helpContent.style.cssText = `
            max-width: 800px !important;
            margin: 0 auto !important;
            display: block !important;
            overflow: visible !important;
            padding: 0 20px !important;
        `;
    }
    
    if (helpList) {
        helpList.style.cssText = `
            list-style-type: decimal !important;
            padding-left: 2rem !important;
            margin: 1.5rem 0 !important;
            display: block !important;
            text-align: left !important;
        `;
        
        // Fix each list item
        const listItems = helpList.querySelectorAll('li');
        listItems.forEach(item => {
            item.style.cssText = `
                display: list-item !important;
                margin-bottom: 0.75rem !important;
                text-align: left !important;
                list-style-position: outside !important;
                padding-left: 0.5rem !important;
            `;
        });
    }
    
    // Fix heading style
    const heading = quickHelpSection.querySelector('h3');
    if (heading) {
        heading.style.cssText = `
            color: #2da0a8 !important;
            margin-bottom: 1.5rem !important;
            text-align: center !important;
            font-size: 1.5rem !important;
            padding-bottom: 0.5rem !important;
            border-bottom: 1px solid rgba(0, 0, 0, 0.05) !important;
        `;
    }
    
    console.log('Quick help styling applied via JavaScript');
});
