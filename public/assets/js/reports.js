const navItems = document.querySelectorAll('.reports-nav-item');
  const mainIcon = document.getElementById('main-report-icon');
  const mainTitle = document.getElementById('main-report-title');
  const mainList = document.getElementById('main-report-list');

  // Realistic sub-reports for different tabs
  const reportData = {
    'Appointments': [
      { icon: 'bi-receipt', text: 'Summary Reports' },
      { icon: 'bi-calendar', text: 'Detailed Reports' }
    ],
    'Categories': [
      { icon: 'bi-grid-3x3', text: 'Category Sales Summary' },
      { icon: 'bi-list-ul', text: 'Category Items List' }
    ],
    'Customers': [
      { icon: 'bi-person-lines-fill', text: 'Customer Directory' },
      { icon: 'bi-graph-up', text: 'Customer Sales History' },
      { icon: 'bi-star', text: 'Top Customers' }
    ],
    'Sales': [
      { icon: 'bi-cart-check', text: 'Daily Sales Report' },
      { icon: 'bi-graph-up-arrow', text: 'Sales by Date Range' },
      { icon: 'bi-receipt', text: 'Tax Summary' }
    ],
    'Inventory Reports': [
      { icon: 'bi-box-seam', text: 'Current Inventory Value' },
      { icon: 'bi-exclamation-triangle', text: 'Low Stock Alerts' },
      { icon: 'bi-arrow-left-right', text: 'Inventory Movement' }
    ],
    'Employees': [
      { icon: 'bi-people', text: 'Employee Attendance' },
      { icon: 'bi-clock-history', text: 'Timeclock History' }
    ]
  };

  navItems.forEach(item => {
    item.addEventListener('click', function() {
      // Remove active class from all
      navItems.forEach(n => n.classList.remove('active'));
      // Add active to clicked
      this.classList.add('active');

      const text = this.innerText.trim();
      const iconClass = this.querySelector('i').className;
      
      mainTitle.innerText = text;
      mainIcon.className = iconClass;

      // Determine sub-reports
      let subReports = reportData[text];
      if (!subReports) {
        // Generic fallback reports
        subReports = [
          { icon: 'bi-receipt', text: text + ' Summary' },
          { icon: 'bi-file-earmark-text', text: 'Detailed ' + text + ' Log' }
        ];
      }

      // Render the list
      mainList.innerHTML = '';
      subReports.forEach(report => {
        const div = document.createElement('div');
        div.className = 'report-item';
        div.innerHTML = `<i class="${report.icon}"></i> ${report.text}`;
        mainList.appendChild(div);
      });
    });
  });




