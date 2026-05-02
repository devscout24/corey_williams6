// Elements
  const listView = document.getElementById('messages-list-view');
  const newView = document.getElementById('new-message-view');
  const detailView = document.getElementById('message-detail-view');

  // Top Buttons
  document.querySelectorAll('.btn-new-message').forEach(btn => {
    btn.addEventListener('click', function() {
      listView.style.display = 'none';
      detailView.style.display = 'none';
      newView.style.display = 'flex';
    });
  });
  
  document.getElementById('btn-inbox').addEventListener('click', function() {
    newView.style.display = 'none';
    detailView.style.display = 'none';
    listView.style.display = 'flex';
  });

  // Clicking a message item opens the detail view
  document.querySelectorAll('.message-item').forEach(item => {
    // Only open if the delete button wasn't clicked
    item.addEventListener('click', function(e) {
      if(e.target.classList.contains('action-delete')) return;
      listView.style.display = 'none';
      newView.style.display = 'none';
      detailView.style.display = 'block';
    });
  });

  // Back to inbox from detail view
  document.getElementById('btn-back-to-inbox').addEventListener('click', function() {
    detailView.style.display = 'none';
    listView.style.display = 'flex';
  });




