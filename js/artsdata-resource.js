class ArtsdataResource extends HTMLElement {

  set org(org) {
    this.innerHTML = `<div><p>
    <b> ${  org.namePref || org.nameEn  || org.nameFr }</b>
   <a href='/member?uri=${ this.artsdataId(org.sameAs)}'> ${ this.artsdataId(org.sameAs)}</a>
   ${ this.officialUrl(org.url[0])  } 
    - ${ org.address.addressLocality  }, ${ org.address.addressRegion  }, ${ org.address.addressCountry  } 
    </p></div>`
  }

  artsdataId(sameAs) {
    if (typeof sameAs == 'object') {
      let artsdataId = ''
      let id = ''
      if (sameAs.length) {
        sameAs.forEach(data => {
          if  (typeof data == 'object') { 
            id = data.id }
          else {
            id = data
          }
          if (id.startsWith("http://kg.artsdata.ca/resource")) {
            artsdataId = id
          } 
        })
      } else {
        artsdataId = sameAs.id
      }
    
      return artsdataId
    }
    else { 
      return sameAs
    }
  }
  officialUrl(url) {
    if (typeof url != 'object') {
      return url
    }
    return ''
  }

  attendanceMode(mode) {
    var modeHtml = ""
    if (mode == "MixedEventAttendanceMode") {
      modeHtml = "AUSSI EN SPECTACLE VIRTUEL"
    }
    else if (mode == "OnlineEventAttendanceMode") {
      modeHtml = "SPECTACLE VIRTUEL"
    }
    return `<span style='color: red;'>${modeHtml}</span>`
  }

  dateFormat(event) {
    return new Date(event.startDate).toLocaleString('fr-FR',  { dateStyle: 'long', timeStyle: 'short', hour12: false, timeZone: 'EST' }) || event.startDateWithoutTime
  }
}


customElements.define('artsdata-resource', ArtsdataResource)