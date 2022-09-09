let ag_startDrag, ag_curPos, ag_currEl, ag_scrubbing, ag_gid
let ag_next, ag_prev
(() => {
    let ag_sps = {}
    let ag_scrubbers = {}
    let ag_grips = {}
    let ag_offsets = {}
    let ag_thumbs = {}
    let ag_scrubber_widths = {}
    let ag_thumbs_widths = {}

    let getOffset = (el) => {
        const rect = el.getBoundingClientRect();
        return {
            left: rect.left + window.scrollX,
            top: rect.top + window.scrollY
        };
    }

    ag_setFullGalleryItems = (items) => {

    }

    ag_prev = (fullGalleryItems, fullGalleryCurrentItem) => {
        let fgci = fullGalleryCurrentItem || fullGalleryItems[0]
        let currentIndex = fullGalleryItems.findIndex((el) => el.src === fgci.src)
        return fullGalleryItems[currentIndex - 1] || fullGalleryItems[fullGalleryItems.length - 1]
    }

    ag_next = (fullGalleryItems, fullGalleryCurrentItem) => {
        let fgci = fullGalleryCurrentItem || fullGalleryItems[0]
        let currentIndex = fullGalleryItems.findIndex((el) => el.src === fgci.src)
        return fullGalleryItems[currentIndex + 1] || fullGalleryItems[0]
    }

    ag_startDrag = (ev, gid) => {
        ev.preventDefault()
        ag_currEl = ev.element
        ag_scrubbing = true
        ag_gid = gid
    }

    ag_endDrag = (ev, gid) => {
        ev.preventDefault()
        ag_currEl = ev.element
        ag_scrubbing = false
        ag_gid = gid
    }

    ag_mounted = (el, gid) => {
        ag_sps[gid] = 0
        ag_scrubbers[gid] = document.querySelector(`#${gid}_scrubber`)
        ag_grips[gid] = document.querySelector(`#${gid}_grip`)
        ag_thumbs[gid] = document.querySelector(`#${gid}_thumbs`)
        ag_scrubber_widths[gid] = ag_scrubbers[gid].clientWidth
        ag_thumbs_widths[gid] = ag_thumbs[gid].offsetWidth

        document.querySelector(`#${gid}`).classList.toggle('noScrub', ag_thumbs_widths[gid] <= ag_scrubber_widths[gid])

        ag_offsets[gid] = getOffset(ag_scrubbers[gid]).left

        ag_currEl = el
        ag_scrubbing = false
        ag_gid = gid

        console.log(window.ag_grips)

    }

    ag_mouseMove = (ev) => {
        if (ag_scrubbing) {
            const newPos = ev.clientX - ag_offsets[ag_gid]
            let newPctPos = (newPos / (ag_scrubber_widths[ag_gid]))
            console.log(newPos, ag_scrubber_widths[ag_gid], newPctPos)
            if (newPctPos >= 0 && newPctPos <= 1) {
                ag_curPos = newPos
                ag_sps[ag_gid] = ag_curPos
            } else if (newPctPos < 0) {
                newPctPos = 0
                ag_curPos = 0
                ag_sps[ag_gid] = ag_curPos
            } else if (newPctPos >= 1) {
                newPctPos = 1
                ag_curPos = 0
                ag_sps[ag_gid] = ag_curPos
            }
            ag_grips[ag_gid].style.left = `${Math.round(newPctPos * 100)}%`
            let pxPos = ag_thumbs_widths[ag_gid] - ag_scrubber_widths[ag_gid]
            const tx = (pxPos) * -newPctPos

            ag_thumbs[ag_gid].style.transform = `translateX(${tx}px)`
        }
    }

    document.addEventListener('mousemove', ag_mouseMove)
    let ag_reinit_timer
    window.addEventListener('resize', (ev) => {
        console.log('reinitializing galleries')
        clearTimeout(ag_reinit_timer)
        ag_reinit_timer = setTimeout(() => {
            Object.keys(ag_scrubbers).forEach((gid) => {
                ag_scrubber_widths[gid] = ag_scrubbers[gid].clientWidth
                ag_thumbs_widths[gid] = ag_thumbs[gid].offsetWidth
                ag_offsets[gid] = getOffset(ag_scrubbers[gid]).left
                ag_scrubbing = false
            })
        })
    })
    document.addEventListener('mouseup', (ev) => {
        if (ag_scrubbing) {
            ev.preventDefault()
            ag_endDrag(ev, ag_curPos, ag_gid)
            ag_scrubbing = false
            return false
        }
    })
})()