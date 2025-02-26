<?php
/**
 * Community Dinners - About Page
 * 
 * This file displays the About page content and allows logged-in users to edit it.
 */

require_once 'includes/functions.php';
require_once 'includes/auth.php';

// Get about page content
function getAboutContent() {
    $aboutFile = DATA_PATH . '/about.json';
    
    if (file_exists($aboutFile)) {
        $content = json_decode(file_get_contents($aboutFile), true);
        if (json_last_error() === JSON_ERROR_NONE) {
            return $content;
        }
    }
    
    // Default content if file doesn't exist or is invalid
    return [
        'content' => '<h2>About Community Dinners</h2><p>Welcome to the Community Dinners website!</p>',
        'last_updated' => '',
        'last_updated_by' => ''
    ];
}

$aboutContent = getAboutContent();

// Include header
include 'includes/header.php';
?>

<div class="about-container">
    <div class="about-header">
        <h2>About</h2>
        <?php if (isLoggedIn()): ?>
        <button id="edit-about-btn" class="btn">Edit Content</button>
        <?php endif; ?>
    </div>
    
    <div class="about-content" id="about-content">
        <?php echo $aboutContent['content']; ?>
    </div>
    
    <?php if (!empty($aboutContent['last_updated'])): ?>
    <div class="about-meta">
        <p>Last updated: <?php echo htmlspecialchars($aboutContent['last_updated']); ?> 
        by <?php echo htmlspecialchars($aboutContent['last_updated_by']); ?></p>
    </div>
    <?php endif; ?>
    
    <?php if (isLoggedIn()): ?>
    <div id="about-editor-container" style="display: none;">
        <div class="editor-toolbar">
            <button type="button" data-command="bold" title="Bold"><strong>B</strong></button>
            <button type="button" data-command="italic" title="Italic"><em>I</em></button>
            <button type="button" data-command="underline" title="Underline"><u>U</u></button>
            <button type="button" data-command="formatBlock" data-value="h2" title="Heading 2">H2</button>
            <button type="button" data-command="formatBlock" data-value="h3" title="Heading 3">H3</button>
            <button type="button" data-command="formatBlock" data-value="h4" title="Heading 4">H4</button>
            <button type="button" data-command="formatBlock" data-value="p" title="Paragraph">P</button>
            <button type="button" data-command="insertUnorderedList" title="Bullet List">• List</button>
            <button type="button" data-command="createLink" title="Insert Link">Link</button>
            <button type="button" data-command="removeFormat" title="Remove Formatting">Clear</button>
        </div>
        
        <div id="editor" contenteditable="true" class="editor-content"></div>
        
        <div class="editor-actions">
            <button id="save-about-btn" class="btn btn-primary">Save Changes</button>
            <button id="cancel-about-btn" class="btn">Cancel</button>
        </div>
    </div>
    <?php endif; ?>
</div>

<?php if (isLoggedIn()): ?>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const aboutContent = document.getElementById('about-content');
    const editBtn = document.getElementById('edit-about-btn');
    const editorContainer = document.getElementById('about-editor-container');
    const editor = document.getElementById('editor');
    const saveBtn = document.getElementById('save-about-btn');
    const cancelBtn = document.getElementById('cancel-about-btn');
    
    // Initialize editor with current content
    editor.innerHTML = aboutContent.innerHTML;
    
    // Show editor when edit button is clicked
    editBtn.addEventListener('click', function() {
        aboutContent.style.display = 'none';
        editBtn.style.display = 'none';
        editorContainer.style.display = 'block';
        editor.focus();
    });
    
    // Hide editor when cancel button is clicked
    cancelBtn.addEventListener('click', function() {
        aboutContent.style.display = 'block';
        editBtn.style.display = 'inline-block';
        editorContainer.style.display = 'none';
    });
    
    // Save changes when save button is clicked
    saveBtn.addEventListener('click', function() {
        const newContent = editor.innerHTML;
        
        // Show loading state
        saveBtn.textContent = 'Saving...';
        saveBtn.disabled = true;
        
        // Send content to server
        const formData = new FormData();
        formData.append('content', newContent);
        
        fetch('api.php?action=update_about', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Update displayed content
                aboutContent.innerHTML = newContent;
                
                // Show success message
                showMessage('success', data.message);
                
                // Hide editor
                aboutContent.style.display = 'block';
                editBtn.style.display = 'inline-block';
                editorContainer.style.display = 'none';
                
                // Update last updated info if provided
                if (data.last_updated) {
                    const metaDiv = document.querySelector('.about-meta');
                    if (metaDiv) {
                        metaDiv.innerHTML = `<p>Last updated: ${data.last_updated} by ${data.last_updated_by}</p>`;
                    } else {
                        const newMetaDiv = document.createElement('div');
                        newMetaDiv.className = 'about-meta';
                        newMetaDiv.innerHTML = `<p>Last updated: ${data.last_updated} by ${data.last_updated_by}</p>`;
                        document.querySelector('.about-container').appendChild(newMetaDiv);
                    }
                }
            } else {
                showMessage('error', data.message || 'Failed to save content');
            }
        })
        .catch(error => {
            showMessage('error', 'An error occurred. Please try again.');
        })
        .finally(() => {
            // Reset button state
            saveBtn.textContent = 'Save Changes';
            saveBtn.disabled = false;
        });
    });
    
    // Set up toolbar buttons
    document.querySelectorAll('.editor-toolbar button').forEach(button => {
        button.addEventListener('click', function() {
            const command = this.dataset.command;
            const value = this.dataset.value || null;
            
            if (command === 'createLink') {
                const url = prompt('Enter the link URL:');
                if (url) {
                    document.execCommand(command, false, url);
                }
            } else {
                document.execCommand(command, false, value);
            }
            
            editor.focus();
        });
    });
});
</script>
<?php endif; ?>

<?php
// Include footer
include 'includes/footer.php';
?>