<script>
  function toggleSidebar() {
    document.getElementById('sidebar').classList.toggle('-translate-x-full');
    document.getElementById('sidebarOverlay').classList.toggle('hidden');
  }

  function closeSidebar() {
    document.getElementById('sidebar').classList.add('-translate-x-full');
    document.getElementById('sidebarOverlay').classList.add('hidden');
  }
</script>

<script>
  function toggleFilter() {
    document.getElementById('filterSection').classList.toggle('hidden');
  }
</script>

<script>
  function toggleAttendanceFilter() {
    document.getElementById('attendanceFilterSection').classList.toggle('hidden');
  }
</script>

<script>
  function showImageModal(src) {
    const modal = document.getElementById('imageModal');
    const zoomed = document.getElementById('zoomedImage');
    zoomed.src = src;
    modal.classList.remove('hidden');
    modal.classList.add('flex');
  }

  function closeImageModal() {
    const modal = document.getElementById('imageModal');
    modal.classList.add('hidden');
    modal.classList.remove('flex');
  }
</script>
