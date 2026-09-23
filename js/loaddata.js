async function loadSelectOptions(url, selectId, placeholderText) {
    const selectEl = document.getElementById(selectId);
    if (!selectEl) return;

    try {
        if (window.location.protocol === 'file:') {
            throw new Error('กรุณาเปิดผ่าน http://localhost/... (ห้ามดับเบิลคลิกเปิดไฟล์ตรงๆ)');
        }

        const response = await fetch(url, { cache: 'no-store' });
        const result = await response.json();

        if (!response.ok || !result.success) {
            throw new Error(result.message || `HTTP ${response.status}`);
        }

        selectEl.innerHTML = '';

        const defaultOption = document.createElement('option');
        defaultOption.value = '';
        defaultOption.disabled = true;
        defaultOption.selected = true;
        defaultOption.textContent = placeholderText;
        selectEl.appendChild(defaultOption);

        if (Array.isArray(result.data)) {
            result.data.forEach((item) => {
                const opt = document.createElement('option');
                opt.value = item.id;
                opt.textContent = item.name;
                selectEl.appendChild(opt);
            });
        }

    } catch (error) {
        selectEl.innerHTML = '';
        const errOption = document.createElement('option');
        errOption.value = '';
        errOption.disabled = true;
        errOption.selected = true;
        errOption.textContent = `ไม่สามารถโหลดข้อมูลได้ (${error.message})`;
        selectEl.appendChild(errOption);
        console.error(`โหลดข้อมูลจาก ${url} ไม่สำเร็จ:`, error);
    }
}
