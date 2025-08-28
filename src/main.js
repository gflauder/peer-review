'use strict';

var parsley = require('parsleyjs');
require('parsleyjs/dist/i18n/fr'); // Import the French locale
require('parsleyjs/dist/i18n/fr.extra'); // Import the extra French

var timeago = global.__timeago = require('timeago.js');
// Register the French locale for timeago.js
global.__timeago.register('fr', require('timeago.js/locales/fr'));
var selectizeRender = {};
var bootstrap = require('bootstrap');

// Polyfill for MSIE
Number.isInteger = Number.isInteger || function(value) {
  return typeof value === 'number' && isFinite(value) && Math.floor(value) === value;
};

$().ready(function() {
  const $articleEditor = $('form#articles_edit');



  // console.log('jQuery and Bootstrap are loaded');
  // ADD: Enhanced form validation for articles_edit
  $articleEditor.on('submit', function(e) {
    const validation = validateReviewersBeforeSubmit();

    if (!validation.valid) {
      e.preventDefault();

      let errorMessage = 'Please complete the following user information:\n\n';
      validation.incompleteFields.forEach(field => {
        errorMessage += `• ${field.field}: ${field.email} - ${field.issue}\n`;
      });

      alert(errorMessage);

      // Focus first invalid field
      const $firstInvalid = $('.is-invalid').first();
      if ($firstInvalid.length) {
        $firstInvalid[0].scrollIntoView({ behavior: 'smooth' });
      }

      return false;
    }
  });

// ADD: Review form validation - completely revised
  // Handle review status change buttons
  $(document).on('click', 'button[name="status"]', function(e) {
    e.preventDefault();

    const $button = $(this);
    const $form = $button.closest('form');
    const statusValue = $button.val();
    const formData = new FormData($form[0]);

    // Add the status to the form data
    formData.append('status', statusValue);

    // Store the current URL to redirect back to after AJAX
    const currentUrl = window.location.href;

    $.ajax({
      url: $form.attr('action'),
      type: 'POST',
      data: formData,
      processData: false,
      contentType: false,
      success: function(response) {
        // Instead of letting the browser handle the response (which might redirect),
        // manually reload the current page or update specific elements
        window.location.href = currentUrl;
      },
      error: function(xhr, status, error) {
        console.error("Error updating review status:", error);
        alert("There was an error updating the review status. Please try again.");
      }
    });

    return false;
  });

  // For regular form submissions (non-status changes), let the browser handle it normally
  $(document).on('submit', 'form[id*="review"]', function(e) {
    // If the form was submitted by a status button, we've already handled it above
    if (e.isDefaultPrevented()) {
      return false;
    }

    console.log("Form being submitted:", this.id);

    // Check if this form has a status input or button
    const hasStatusElement = $(this).find('input[name="status"], button[name="status"]').length > 0;

    // Check if form has peer selection - only on forms that add new peer reviewers
    const hasPeerSelection = $(this).find('select[name="peers[]"]').length > 0 &&
        $(this).find('select[name="peers[]"]').val() !== null;

    console.log("Has status element:", hasStatusElement);
    console.log("Has peer selection:", hasPeerSelection);

    // If this is a status change form (has status element) and doesn't have peer selection
    // OR if it has an ID field (editing existing review), skip validation
    if ((hasStatusElement && !hasPeerSelection) || $(this).find('input[name="id"]').length > 0) {
      console.log("Skipping validation - status change or existing review edit");
      return true;
    }

    // Only validate if we're adding new peer reviewers
    if (hasPeerSelection) {
      const validation = validateReviewersBeforeSubmit();

      if (!validation.valid) {
        e.preventDefault();
        alert('Please complete all peer reviewer information before adding reviewers.');
        return false;
      }
    }

    return true;

});




  function updateCopyright() {
    const $link = $('a#copyright_link');


    if (!$link.attr('baseHREF')) {
      $link.attr('baseHREF', $link.attr('href'));
    }
    $link.attr(
        'href',
        $link.attr('baseHREF')
        + '&t='
        + encodeURIComponent($articleEditor.find('#title').val())
        + '&aa='
        + encodeURIComponent($articleEditor.find('#authors\\[\\]').val())
    );
  }

  if ($articleEditor.length) {
    setTimeout(updateCopyright, 1000);
    $articleEditor.find('#title').on('change', updateCopyright);
    $articleEditor.find('#authors\\[\\]').on('change', updateCopyright);
    $articleEditor.find('#plagiarism_ok').on('change', function() {
      var $textarea = $('#plagiarism');
      if ($(this).prop('checked')) {
        $textarea.val('1').css('display', 'none');
      } else {
        $textarea.val('').css('display', 'block');
      }
    });
  }

  const select = (el, all = false) => {
    el = el.trim();
    if (all) {
      return [...document.querySelectorAll(el)];
    } else {
      return document.querySelector(el);
    }
  };

  const on = (type, el, listener, all = false) => {
    if (all) {
      select(el, all).forEach(e => e.addEventListener(type, listener));
    } else {
      select(el, all).addEventListener(type, listener);
    }
  };

  const onscroll = (el, listener) => {
    el.addEventListener('scroll', listener);
  };

  if (select('#sidebarCollapse')) {
    on('click', '#sidebarCollapse', function(e) {
      // Toggle the sidebar class on the body
      select('body').classList.toggle('toggle-sidebar');
    });
  }

  let isSidebarCollapsed = false;

/* ----  Sidebar collapse Button  ----*/

// Get references to the sidebar and button
  const sidebarToggleBtn = document.getElementById('sidebarCollapse');
  const sidebar = document.querySelector('.sidebar');

// Function to dynamically determine the icon for the sidebar toggle button
  function updateSidebarButtonIcon() {

    if (!sidebarToggleBtn) return;

    const screenWidth = window.innerWidth;

    // If the sidebar is collapsed, show the right arrow (`>`)
    if (sidebar.classList.contains('collapsed')) {
      sidebarToggleBtn.innerHTML = '<i class="bi bi-list"></i>';
      return; // Exit the function, no further checks are required
    }

    // If the sidebar is expanded, determine the icon based on screen width
    if (screenWidth <= 1199) {
      sidebarToggleBtn.innerHTML = '<i class="bi bi-list"></i>'; // Left arrow for expanded sidebar on smaller screens
    } else {
      sidebarToggleBtn.innerHTML = '<i class="bi bi-list"></i>'; // Left arrow for expanded sidebar on larger screens
    }
  }

// Ensure correct button state and icon when the page loads
  document.addEventListener('DOMContentLoaded', function () {
    if (sidebar.classList.contains('collapsed')) {
      // If the sidebar is collapsed on page load, set the button to show the right arrow (`>`)
      sidebarToggleBtn.innerHTML = '<i class="bi bi-list"></i>';
    } else {
      // If the sidebar is expanded, determine the button icon based on screen size
      updateSidebarButtonIcon();
    }
  });

// Toggle the sidebar state and update the button on button click
  if (sidebar && sidebarToggleBtn) {
    // Toggle the sidebar state and update the button on button click
    sidebarToggleBtn.addEventListener('click', function () {
      sidebar.classList.toggle('collapsed'); // Collapse or expand the sidebar
      sidebarToggleBtn.classList.toggle('collapsed'); // Sync the button state
      updateSidebarButtonIcon(); // Update the button icon
    });
  }


// Listen for window resize events and update the button icon dynamically
  window.addEventListener('resize', updateSidebarButtonIcon);
/* ----  END Sidebar collapse Button --- */



  if (select('.search-bar-toggle')) {
    on('click', '.search-bar-toggle', function(e) {
      select('.search-bar').classList.toggle('search-bar-show');
    });
  }

  /**
   * Navbar links active state on scroll
   */
  let navbarlinks = select('#navbar .scrollto', true);
  const navbarlinksActive = () => {
    let position = window.scrollY + 200;
    navbarlinks.forEach(navbarlink => {
      if (!navbarlink.hash) return;
      let section = select(navbarlink.hash);
      if (!section) return;
      if (position >= section.offsetTop && position <= (section.offsetTop + section.offsetHeight)) {
        navbarlink.classList.add('active');
      } else {
        navbarlink.classList.remove('active');
      }
    });
  };
  window.addEventListener('load', navbarlinksActive);
  onscroll(document, navbarlinksActive);

  /**
   * Header Bar links active state on scroll
   */
  let selectHeader = select('#header');
  if (selectHeader) {
    const headerScrolled = () => {
      if (window.scrollY > 100) {
        selectHeader.classList.add('header-scrolled');
      } else {
        selectHeader.classList.remove('header-scrolled');
      }
    };
    window.addEventListener('load', headerScrolled);
    onscroll(document, headerScrolled);
  }

  let backtotop = select('.back-to-top');
  if (backtotop) {
    const toggleBacktotop = () => {
      if (window.scrollY > 100) {
        backtotop.classList.add('active');
      } else {
        backtotop.classList.remove('active');
      }
    };
    window.addEventListener('load', toggleBacktotop);
    onscroll(document, toggleBacktotop);
  }

  var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
  var tooltipList = tooltipTriggerList.map(function(tooltipTriggerEl) {
    return new bootstrap.Tooltip(tooltipTriggerEl);
  });

  var dropdownElementList = [].slice.call(document.querySelectorAll('.dropdown-toggle'));
  var dropdownList = dropdownElementList.map(function(dropdownToggleEl) {
    return new bootstrap.Dropdown(dropdownToggleEl);
  });

// ADD: Validation function for reviewers before form submit
  function validateReviewersBeforeSubmit() {
    // Check if this is a review form with peers
    const $peerFields = $('select[name="peers[]"]');
    if ($peerFields.length === 0 || $peerFields.val() === null) {
      // No peer fields or no peers selected - validation passes
      return { valid: true, incompleteFields: [] };
    }

    let hasIncompleteReviewers = false;
    const incompleteFields = [];

    $('.select-users, [name="peers[]"]').each(function() {
      const $select = $(this);

      // Only validate if it's a selectize instance
      if ($select[0].selectize) {
        const selectizeInstance = $select[0].selectize;
        const validation = validateAllSelectizeUsers(selectizeInstance);

        if (!validation.valid) {
          hasIncompleteReviewers = true;
          const fieldLabel = $select.data('label') || 'User field';

          validation.incompleteUsers.forEach(user => {
            incompleteFields.push({
              field: fieldLabel,
              email: user.email || 'Unknown',
              issue: user.name ? 'Complete' : 'Name required'
            });
          });

          $select.addClass('is-invalid');
        } else {
          $select.removeClass('is-invalid');
        }
      }
    });

    return {
      valid: !hasIncompleteReviewers,
      incompleteFields: incompleteFields
    };
  }


});