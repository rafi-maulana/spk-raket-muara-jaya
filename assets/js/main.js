/**
 * main.js — SPK Toko Raket Bulutangkis
 * Decision Support System — Frontend Utilities
 * Vanilla JS only (no jQuery)
 */

document.addEventListener('DOMContentLoaded', function () {

  /* =========================================================
   * 1. WEIGHT VALIDATION (admin/kriteria.php)
   *    Real-time sum of .weight-input fields.
   *    Counter shown green if sum == 1.00, red otherwise.
   *    Prevents form submission when sum != 1.00.
   * ========================================================= */
  (function initWeightValidation() {
    const weightInputs = document.querySelectorAll('.weight-input');
    if (!weightInputs.length) return;

    // Create or find a live counter element
    let counter = document.getElementById('weight-counter');
    if (!counter) {
      counter = document.createElement('div');
      counter.id = 'weight-counter';
      counter.className = 'mt-2 fw-semibold fs-6';
      const firstInput = weightInputs[0];
      const parent = firstInput.closest('form') || firstInput.parentElement;
      const submitBtn = parent.querySelector('[type="submit"]');
      if (submitBtn) {
        submitBtn.parentElement.insertBefore(counter, submitBtn);
      } else {
        parent.appendChild(counter);
      }
    }

    function calculateWeightSum() {
      let total = 0;
      weightInputs.forEach(function (input) {
        const val = parseFloat(input.value.replace(',', '.')) || 0;
        total += val;
      });
      return Math.round(total * 1000) / 1000;
    }

    function updateWeightCounter() {
      const total = calculateWeightSum();
      const isValid = Math.abs(total - 1.0) < 0.001;
      counter.textContent = 'Total Bobot: ' + total.toFixed(3) + (isValid ? ' \u2714 Valid' : ' \u2718 Harus = 1.00');
      counter.style.color = isValid ? '#198754' : '#dc3545';
      weightInputs.forEach(function (input) {
        input.classList.toggle('is-valid', isValid);
        input.classList.toggle('is-invalid', !isValid);
      });
    }

    weightInputs.forEach(function (input) {
      input.addEventListener('input', updateWeightCounter);
      input.addEventListener('change', updateWeightCounter);
    });

    const weightForm = weightInputs[0].closest('form');
    if (weightForm) {
      weightForm.addEventListener('submit', function (e) {
        const total = calculateWeightSum();
        if (Math.abs(total - 1.0) >= 0.001) {
          e.preventDefault();
          e.stopPropagation();
          counter.textContent = 'Total Bobot: ' + total.toFixed(3) + ' \u2718 Total bobot harus tepat 1.00 sebelum menyimpan!';
          counter.style.color = '#dc3545';
          counter.scrollIntoView({ behavior: 'smooth', block: 'center' });
        }
      });
    }

    updateWeightCounter();
  })();


  /* =========================================================
   * 2. BUDGET INPUT FORMATTER (Indonesian thousand separators)
   * ========================================================= */
  (function initBudgetFormatter() {
    const budgetSelectors = [
      '.budget-input',
      '#budget',
      '#anggaran',
      'input[name="budget"]',
      'input[name="anggaran"]',
    ];

    const budgetInputs = document.querySelectorAll(budgetSelectors.join(','));
    if (!budgetInputs.length) return;

    function toIndonesianFormat(value) {
      const digits = value.replace(/\D/g, '');
      if (!digits) return '';
      return digits.replace(/\B(?=(\d{3})+(?!\d))/g, '.');
    }

    function fromIndonesianFormat(value) {
      return parseInt(value.replace(/\./g, ''), 10) || 0;
    }

    budgetInputs.forEach(function (input) {
      let hiddenInput = document.getElementById(input.id + '_raw');
      if (!hiddenInput && input.name) {
        hiddenInput = document.querySelector('input[name="' + input.name + '_raw"]');
      }
      input.setAttribute('inputmode', 'numeric');

      input.addEventListener('input', function () {
        const formatted = toIndonesianFormat(input.value);
        input.value = formatted;
        if (hiddenInput) {
          hiddenInput.value = fromIndonesianFormat(formatted);
        }
      });

      input.addEventListener('blur', function () {
        if (!input.value) return;
        input.value = toIndonesianFormat(input.value);
      });

      if (input.value) {
        input.value = toIndonesianFormat(input.value);
      }
    });
  })();


  /* =========================================================
   * 3. SMOOTH SCROLL for anchor links
   * ========================================================= */
  (function initSmoothScroll() {
    document.querySelectorAll('a[href^="#"]').forEach(function (anchor) {
      anchor.addEventListener('click', function (e) {
        const targetId = anchor.getAttribute('href');
        if (targetId === '#') return;
        const target = document.querySelector(targetId);
        if (target) {
          e.preventDefault();
          target.scrollIntoView({ behavior: 'smooth', block: 'start' });
        }
      });
    });
  })();


  /* =========================================================
   * 4. SIDEBAR TOGGLE (admin panel)
   * ========================================================= */
  (function initSidebarToggle() {
    const toggleBtns = document.querySelectorAll('#sidebarToggle, .sidebar-toggle');
    const sidebar = document.getElementById('sidebar') || document.querySelector('.sidebar');
    const mainContent = document.getElementById('main-content') || document.querySelector('.main-content');

    if (!toggleBtns.length || !sidebar) return;

    if (localStorage.getItem('sidebarCollapsed') === 'true') {
      sidebar.classList.add('collapsed');
      if (mainContent) mainContent.classList.add('sidebar-collapsed');
    }

    toggleBtns.forEach(function (btn) {
      btn.addEventListener('click', function () {
        sidebar.classList.toggle('collapsed');
        if (mainContent) mainContent.classList.toggle('sidebar-collapsed');
        localStorage.setItem('sidebarCollapsed', sidebar.classList.contains('collapsed'));
      });
    });
  })();


  /* =========================================================
   * 5. DELETE CONFIRMATION MODAL (Bootstrap 5)
   * ========================================================= */
  (function initDeleteConfirmation() {
    if (!document.getElementById('deleteConfirmModal')) {
      const modalHTML = `
        <div class="modal fade" id="deleteConfirmModal" tabindex="-1" aria-labelledby="deleteConfirmModalLabel" aria-hidden="true">
          <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow">
              <div class="modal-header bg-danger text-white border-0">
                <h5 class="modal-title" id="deleteConfirmModalLabel">
                  <i class="bi bi-exclamation-triangle-fill me-2"></i>Konfirmasi Hapus
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Tutup"></button>
              </div>
              <div class="modal-body py-4">
                <p class="mb-0 fs-6" id="deleteConfirmMessage">
                  Apakah Anda yakin ingin menghapus data ini? Tindakan ini tidak dapat dibatalkan.
                </p>
              </div>
              <div class="modal-footer border-0">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                  <i class="bi bi-x-circle me-1"></i>Batal
                </button>
                <a href="#" id="deleteConfirmBtn" class="btn btn-danger">
                  <i class="bi bi-trash-fill me-1"></i>Ya, Hapus
                </a>
              </div>
            </div>
          </div>
        </div>`;
      document.body.insertAdjacentHTML('beforeend', modalHTML);
    }

    const deleteModal = document.getElementById('deleteConfirmModal');
    const deleteConfirmMessage = document.getElementById('deleteConfirmMessage');

    if (!deleteModal) return;

    let bsDeleteModal = null;
    if (typeof bootstrap !== 'undefined') {
      bsDeleteModal = new bootstrap.Modal(deleteModal);
    }

    document.addEventListener('click', function (e) {
      const trigger = e.target.closest('[data-confirm-delete]');
      if (!trigger) return;
      e.preventDefault();

      const message = trigger.getAttribute('data-confirm-delete');
      const href = trigger.getAttribute('href') || trigger.getAttribute('data-href');
      const form = trigger.getAttribute('data-form');

      if (message && deleteConfirmMessage) {
        deleteConfirmMessage.textContent = message;
      } else if (deleteConfirmMessage) {
        deleteConfirmMessage.textContent = 'Apakah Anda yakin ingin menghapus data ini? Tindakan ini tidak dapat dibatalkan.';
      }

      const deleteConfirmBtn = document.getElementById('deleteConfirmBtn');
      if (deleteConfirmBtn) {
        const newBtn = deleteConfirmBtn.cloneNode(true);
        deleteConfirmBtn.parentNode.replaceChild(newBtn, deleteConfirmBtn);
        const freshBtn = document.getElementById('deleteConfirmBtn');

        if (href) {
          freshBtn.href = href;
          freshBtn.onclick = null;
        } else if (form) {
          freshBtn.href = '#';
          freshBtn.onclick = function () {
            const targetForm = document.getElementById(form) || document.querySelector(form);
            if (targetForm) targetForm.submit();
          };
        }
      }

      if (bsDeleteModal) {
        bsDeleteModal.show();
      } else {
        const msg = deleteConfirmMessage ? deleteConfirmMessage.textContent : 'Hapus data ini?';
        if (confirm(msg)) {
          if (href) window.location.href = href;
        }
      }
    });
  })();


  /* =========================================================
   * 6. AUTO-DISMISS ALERTS after 5 seconds
   * ========================================================= */
  (function initAutoDismissAlerts() {
    const alerts = document.querySelectorAll('.alert.alert-dismissible');
    alerts.forEach(function (alert) {
      if (alert.classList.contains('alert-danger')) return;
      setTimeout(function () {
        if (alert && alert.parentNode) {
          alert.style.transition = 'opacity 0.6s ease';
          alert.style.opacity = '0';
          setTimeout(function () {
            if (alert.parentNode) {
              if (typeof bootstrap !== 'undefined') {
                const bsAlert = bootstrap.Alert.getOrCreateInstance(alert);
                bsAlert.close();
              } else {
                alert.remove();
              }
            }
          }, 600);
        }
      }, 5000);
    });
  })();


  /* =========================================================
   * 7. SCORE INPUT VALIDATION (raket_form.php) 1-10
   * ========================================================= */
  (function initScoreValidation() {
    const scoreInputs = document.querySelectorAll('.score-input');
    if (!scoreInputs.length) return;

    scoreInputs.forEach(function (input) {
      input.setAttribute('min', '1');
      input.setAttribute('max', '10');
      input.setAttribute('step', '1');

      function validate() {
        const val = parseFloat(input.value);
        const isValid = !isNaN(val) && val >= 1 && val <= 10;
        input.classList.toggle('is-valid', isValid && input.value !== '');
        input.classList.toggle('is-invalid', !isValid && input.value !== '');
        let feedback = input.nextElementSibling;
        if (!feedback || !feedback.classList.contains('invalid-feedback')) {
          feedback = document.createElement('div');
          feedback.className = 'invalid-feedback';
          input.parentNode.insertBefore(feedback, input.nextSibling);
        }
        feedback.textContent = isValid || input.value === '' ? '' : 'Nilai harus antara 1 sampai 10.';
      }

      input.addEventListener('input', validate);
      input.addEventListener('blur', validate);
    });

    const scoreForm = scoreInputs[0].closest('form');
    if (scoreForm) {
      scoreForm.addEventListener('submit', function (e) {
        let hasError = false;
        scoreInputs.forEach(function (input) {
          const val = parseFloat(input.value);
          if (isNaN(val) || val < 1 || val > 10) {
            hasError = true;
            input.classList.add('is-invalid');
          }
        });
        if (hasError) {
          e.preventDefault();
          const firstInvalid = scoreForm.querySelector('.score-input.is-invalid');
          if (firstInvalid) firstInvalid.scrollIntoView({ behavior: 'smooth', block: 'center' });
        }
      });
    }
  })();


  /* =========================================================
   * 8. PRINT FUNCTION (cetak.php)
   * ========================================================= */
  (function initPrintFunction() {
    const printBtns = document.querySelectorAll('.btn-print, #btnPrint');
    printBtns.forEach(function (btn) {
      btn.addEventListener('click', function (e) {
        e.preventDefault();
        window.print();
      });
    });

    const params = new URLSearchParams(window.location.search);
    if (params.get('print') === '1') {
      window.addEventListener('load', function () {
        setTimeout(window.print, 500);
      });
    }
  })();


  /* =========================================================
   * 9. ANIMATE PROGRESS BARS ON SCROLL (IntersectionObserver)
   * ========================================================= */
  (function initProgressBarAnimation() {
    const progressBars = document.querySelectorAll('.progress-bar[data-width]');
    if (!progressBars.length) return;

    progressBars.forEach(function (bar) {
      bar.style.width = '0%';
      bar.style.transition = 'width 1s ease-in-out';
    });

    if ('IntersectionObserver' in window) {
      const observer = new IntersectionObserver(function (entries) {
        entries.forEach(function (entry) {
          if (entry.isIntersecting) {
            const bar = entry.target;
            const targetWidth = bar.getAttribute('data-width') || bar.getAttribute('aria-valuenow') + '%';
            bar.style.width = targetWidth;
            observer.unobserve(bar);
          }
        });
      }, { threshold: 0.2 });

      progressBars.forEach(function (bar) { observer.observe(bar); });
    } else {
      progressBars.forEach(function (bar) {
        const targetWidth = bar.getAttribute('data-width') || bar.getAttribute('aria-valuenow') + '%';
        bar.style.width = targetWidth;
      });
    }
  })();


  /* =========================================================
   * 10. TABLE ROW HIGHLIGHT ON HOVER
   * ========================================================= */
  (function initTableRowHighlight() {
    const tables = document.querySelectorAll('table.table tbody');
    tables.forEach(function (tbody) {
      const rows = tbody.querySelectorAll('tr');
      rows.forEach(function (row) {
        row.style.cursor = 'pointer';
        row.style.transition = 'background-color 0.15s ease';

        row.addEventListener('mouseenter', function () {
          row.style.backgroundColor = 'rgba(13, 110, 253, 0.06)';
        });
        row.addEventListener('mouseleave', function () {
          if (!row.classList.contains('row-selected')) {
            row.style.backgroundColor = '';
          }
        });
        row.addEventListener('click', function (e) {
          if (e.target.closest('a, button, input, select, textarea')) return;
          rows.forEach(function (r) {
            r.classList.remove('row-selected');
            r.style.backgroundColor = '';
          });
          row.classList.add('row-selected');
          row.style.backgroundColor = 'rgba(13, 110, 253, 0.12)';
        });
      });
    });
  })();


  /* =========================================================
   * 11. FORM VALIDATION FEEDBACK STYLING
   * ========================================================= */
  (function initFormValidationFeedback() {
    const forms = document.querySelectorAll('form.needs-validation');
    forms.forEach(function (form) {
      form.addEventListener('submit', function (e) {
        if (!form.checkValidity()) {
          e.preventDefault();
          e.stopPropagation();
        }
        form.classList.add('was-validated');
      }, false);
    });

    document.querySelectorAll('form.needs-validation input, form.needs-validation select, form.needs-validation textarea')
      .forEach(function (field) {
        field.addEventListener('blur', function () {
          if (field.value !== '') {
            if (field.checkValidity()) {
              field.classList.add('is-valid');
              field.classList.remove('is-invalid');
            } else {
              field.classList.add('is-invalid');
              field.classList.remove('is-valid');
            }
          }
        });
      });
  })();


  /* =========================================================
   * 12. IMAGE PREVIEW — Racket Image Upload
   * ========================================================= */
  (function initImagePreview() {
    const imageInputs = document.querySelectorAll('#gambar, .image-upload');
    imageInputs.forEach(function (input) {
      input.addEventListener('change', function () {
        const file = input.files && input.files[0];
        if (!file) return;

        if (!file.type.startsWith('image/')) {
          alert('File yang dipilih bukan gambar. Pilih file gambar yang valid.');
          input.value = '';
          return;
        }

        let preview = document.getElementById('imagePreview') || document.querySelector('.image-preview');
        if (!preview) {
          preview = document.createElement('img');
          preview.id = 'imagePreview';
          preview.className = 'img-thumbnail mt-2';
          preview.style.maxHeight = '200px';
          preview.style.maxWidth = '100%';
          preview.style.objectFit = 'contain';
          preview.style.display = 'block';
          input.parentNode.insertBefore(preview, input.nextSibling);
        }

        const reader = new FileReader();
        reader.onload = function (e) {
          if (preview.tagName === 'IMG') {
            preview.src = e.target.result;
            preview.style.display = 'block';
          } else {
            let img = preview.querySelector('img');
            if (!img) {
              img = document.createElement('img');
              img.className = 'img-thumbnail mt-2';
              img.style.maxHeight = '200px';
              img.style.maxWidth = '100%';
              img.style.objectFit = 'contain';
              preview.appendChild(img);
            }
            img.src = e.target.result;
          }
        };
        reader.readAsDataURL(file);
      });
    });
  })();


  /* =========================================================
   * 13. BOOTSTRAP 5 TOOLTIP INITIALIZATION
   * ========================================================= */
  (function initTooltips() {
    if (typeof bootstrap === 'undefined' || !bootstrap.Tooltip) return;
    const tooltipEls = document.querySelectorAll('[data-bs-toggle="tooltip"]');
    tooltipEls.forEach(function (el) {
      new bootstrap.Tooltip(el, {
        trigger: 'hover focus',
        boundary: 'window',
      });
    });
  })();


  /* =========================================================
   * POPOVER INITIALIZATION (complements tooltips)
   * ========================================================= */
  (function initPopovers() {
    if (typeof bootstrap === 'undefined' || !bootstrap.Popover) return;
    const popoverEls = document.querySelectorAll('[data-bs-toggle="popover"]');
    popoverEls.forEach(function (el) {
      new bootstrap.Popover(el, { trigger: 'hover focus' });
    });
  })();


  /* =========================================================
   * ACTIVE NAV LINK HIGHLIGHTING
   * ========================================================= */
  (function initActiveNavLink() {
    const currentPath = window.location.pathname;
    const navLinks = document.querySelectorAll('.navbar-nav .nav-link, .sidebar .nav-link, .sidebar-menu a');
    navLinks.forEach(function (link) {
      const linkPath = link.getAttribute('href');
      if (!linkPath) return;
      if (currentPath.endsWith(linkPath) || currentPath.includes(linkPath.replace(/^\.\.\//, ''))) {
        link.classList.add('active');
        const parentCollapse = link.closest('.collapse');
        if (parentCollapse) {
          parentCollapse.classList.add('show');
          const toggler = document.querySelector('[data-bs-target="#' + parentCollapse.id + '"]');
          if (toggler) toggler.setAttribute('aria-expanded', 'true');
        }
      }
    });
  })();


  /* =========================================================
   * RECOMMENDATION SECTION SCROLL
   * ========================================================= */
  (function initRecommendationScroll() {
    const hasil = document.getElementById('hasil-rekomendasi');
    if (hasil) {
      setTimeout(function () {
        hasil.scrollIntoView({ behavior: 'smooth', block: 'start' });
      }, 300);
    }
  })();


  /* =========================================================
   * SESSION TIMEOUT WARNING
   * ========================================================= */
  (function initSessionTimeoutWarning() {
    const expiresAttr = document.body.getAttribute('data-session-expires');
    if (!expiresAttr) return;
    const expiresAt = parseInt(expiresAttr, 10) * 1000;
    const warnBefore = 5 * 60 * 1000;

    function checkSession() {
      const now = Date.now();
      const remaining = expiresAt - now;
      if (remaining <= 0) {
        window.location.href = 'logout.php?reason=timeout';
        return;
      }
      if (remaining <= warnBefore) {
        const mins = Math.ceil(remaining / 60000);
        window.showToast(
          'Sesi Anda akan berakhir dalam ' + mins + ' menit. Segera simpan pekerjaan Anda.',
          'warning',
          8000
        );
      }
    }
    setInterval(checkSession, 60 * 1000);
  })();


  /* =========================================================
   * UNSAVED CHANGES WARNING
   * ========================================================= */
  (function initUnsavedChangesWarning() {
    const forms = document.querySelectorAll('form[data-unsaved-warning]');
    forms.forEach(function (form) {
      let isDirty = false;
      form.querySelectorAll('input, select, textarea').forEach(function (field) {
        field.addEventListener('change', function () { isDirty = true; });
        field.addEventListener('input', function () { isDirty = true; });
      });
      form.addEventListener('submit', function () { isDirty = false; });
      window.addEventListener('beforeunload', function (e) {
        if (isDirty) {
          e.preventDefault();
          e.returnValue = 'Ada perubahan yang belum disimpan. Yakin ingin meninggalkan halaman?';
        }
      });
    });
  })();


  /* =========================================================
   * CLIENT-SIDE TABLE SEARCH FILTER
   * ========================================================= */
  (function initTableSearch() {
    const searchInput = document.getElementById('tableSearch');
    if (!searchInput) return;
    const targetTableId = searchInput.getAttribute('data-table');
    const table = targetTableId ? document.getElementById(targetTableId) : document.querySelector('table');
    if (!table) return;
    const tbody = table.querySelector('tbody');
    if (!tbody) return;

    searchInput.addEventListener('input', function () {
      const query = searchInput.value.toLowerCase().trim();
      const rows = tbody.querySelectorAll('tr');
      let visibleCount = 0;
      rows.forEach(function (row) {
        const text = row.textContent.toLowerCase();
        const match = text.includes(query);
        row.style.display = match ? '' : 'none';
        if (match) visibleCount++;
      });
      let noResults = table.parentElement.querySelector('.no-results-msg');
      if (visibleCount === 0 && query !== '') {
        if (!noResults) {
          noResults = document.createElement('p');
          noResults.className = 'no-results-msg text-center text-muted py-3';
          table.parentElement.insertBefore(noResults, table.nextSibling);
        }
        noResults.textContent = 'Tidak ada data yang cocok dengan pencarian "' + searchInput.value + '".';
        noResults.style.display = '';
      } else if (noResults) {
        noResults.style.display = 'none';
      }
    });
  })();


  /* =========================================================
   * COPY TO CLIPBOARD UTILITY
   * ========================================================= */
  (function initCopyToClipboard() {
    document.querySelectorAll('[data-copy-target]').forEach(function (btn) {
      btn.addEventListener('click', function () {
        const targetSel = btn.getAttribute('data-copy-target');
        const target = document.querySelector(targetSel);
        if (!target) return;
        const text = target.value || target.textContent;
        navigator.clipboard.writeText(text.trim()).then(function () {
          const original = btn.innerHTML;
          btn.innerHTML = '<i class="bi bi-check-lg"></i> Disalin!';
          btn.disabled = true;
          setTimeout(function () {
            btn.innerHTML = original;
            btn.disabled = false;
          }, 2000);
        }).catch(function () {
          alert('Gagal menyalin teks. Silakan salin secara manual.');
        });
      });
    });
  })();

}); // END DOMContentLoaded


/* =========================================================
 * 14. GLOBAL UTILITY FUNCTIONS
 * ========================================================= */

/**
 * formatRupiah(number, withSymbol)
 * Formats a number as Indonesian Rupiah.
 * e.g. formatRupiah(1500000) => "Rp 1.500.000"
 *
 * @param {number|string} number
 * @param {boolean} [withSymbol=true]
 * @returns {string}
 */
window.formatRupiah = function formatRupiah(number, withSymbol) {
  if (withSymbol === undefined) withSymbol = true;
  const num = parseInt(String(number).replace(/\D/g, ''), 10);
  if (isNaN(num)) return withSymbol ? 'Rp 0' : '0';
  const formatted = num.toString().replace(/\B(?=(\d{3})+(?!\d))/g, '.');
  return withSymbol ? 'Rp ' + formatted : formatted;
};

/**
 * parseRupiah(str)
 * Parses an Indonesian Rupiah string back to a number.
 * e.g. parseRupiah("Rp 1.500.000") => 1500000
 *
 * @param {string} str
 * @returns {number}
 */
window.parseRupiah = function parseRupiah(str) {
  return parseInt(String(str).replace(/[^\d]/g, ''), 10) || 0;
};

/**
 * formatNumber(number)
 * Indonesian thousand separators, no currency symbol.
 * e.g. formatNumber(2500000) => "2.500.000"
 *
 * @param {number|string} number
 * @returns {string}
 */
window.formatNumber = function formatNumber(number) {
  return window.formatRupiah(number, false);
};

/**
 * showToast(message, type, duration)
 * Shows a Bootstrap 5 toast notification.
 *
 * @param {string} message
 * @param {'success'|'danger'|'warning'|'info'} [type='info']
 * @param {number} [duration=4000]
 */
window.showToast = function showToast(message, type, duration) {
  if (!type) type = 'info';
  if (!duration) duration = 4000;

  let container = document.getElementById('toast-container');
  if (!container) {
    container = document.createElement('div');
    container.id = 'toast-container';
    container.className = 'toast-container position-fixed bottom-0 end-0 p-3';
    container.style.zIndex = '1090';
    document.body.appendChild(container);
  }

  const iconMap = { success: '\u2714', danger: '\u2718', warning: '\u26a0', info: '\u2139' };
  const toastEl = document.createElement('div');
  toastEl.className = 'toast align-items-center text-bg-' + type + ' border-0 show';
  toastEl.setAttribute('role', 'alert');
  toastEl.setAttribute('aria-live', 'assertive');
  toastEl.innerHTML =
    '<div class="d-flex">' +
      '<div class="toast-body fw-semibold">' + (iconMap[type] || '\u2139') + ' ' + message + '</div>' +
      '<button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Tutup"></button>' +
    '</div>';

  container.appendChild(toastEl);

  if (typeof bootstrap !== 'undefined' && bootstrap.Toast) {
    const bsToast = new bootstrap.Toast(toastEl, { delay: duration });
    bsToast.show();
  }

  setTimeout(function () {
    if (toastEl.parentNode) {
      toastEl.style.transition = 'opacity 0.4s ease';
      toastEl.style.opacity = '0';
      setTimeout(function () {
        if (toastEl.parentNode) toastEl.remove();
      }, 400);
    }
  }, duration);
};
