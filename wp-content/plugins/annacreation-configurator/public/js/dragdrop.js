const canvas = document.getElementById('canvas');

document.querySelectorAll('.draggable-item').forEach(el => {

    el.addEventListener('mousedown', () => {

        const clone = el.cloneNode(true);
        clone.classList.add('placed');

        clone.style.position = "absolute";

        canvas.appendChild(clone);

        clone.onmousedown = function (e) {

            let shiftX = e.clientX - clone.getBoundingClientRect().left;
            let shiftY = e.clientY - clone.getBoundingClientRect().top;

            function move(e) {
                clone.style.left = e.pageX - shiftX + 'px';
                clone.style.top = e.pageY - shiftY + 'px';
            }

            document.addEventListener('mousemove', move);

            document.onmouseup = () => {
                document.removeEventListener('mousemove', move);
                document.onmouseup = null;
            };
        };

    });

});