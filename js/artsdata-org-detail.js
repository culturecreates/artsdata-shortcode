class ArtsdataOrgDetail extends HTMLElement {

  set org(org) {
    this.innerHTML = `<div>
    <h3> ${  org.namePref || org.nameEn  || org.nameFr }</h3>
    <p> ${ org.address.addressLocality  }, ${ org.address.addressRegion  }, ${ org.address.addressCountry  } </p>
    <p> <a href="${ this.officialUrl(org.url[0])}">${this.officialUrl(org.url[0])}</a> </p>
    <br>
    <p> Organization Type:<b> ${this.organizationType(org.additionalType) }</b> </p>
    <p> Disciplines:<b> ${this.disciplines(org.additionalType) }</b> </p>
    <p> Presentation Format:<b> ${this.presentationFormat(org.additionalType) }</b> </p>
    <br>
    <p> Artsdata ID:  <a href='${ org.id}'> ${ org.id.split('/resource/')[1]}</a>
    <p> Wikidata ID: <a href='http://wikidata.org/entity/${this.linkExtraction(org.identifier, "Q")}'>${this.linkExtraction(org.identifier, "Q") || "none"}</a> </p>
    <p> Canadian Business Number: ${org.businessNumber} </p>
    <br> ${this.socialMedia(org) }
    
     <br>
     Links: ${this.links(org.sameAs)} 
    <p> Venues: <br> ${this.venues(org.location)}  </b></p>
    </div>`
  }

  socialMedia(org) {
    let socialHtml = ''
    if (org.sameAs) {
      socialHtml += this.formatLink(org.sameAs, "facebook.com", "Facebook") 
      socialHtml += this.formatLink(org.sameAs, "twitter.com", "Twitter") 
      socialHtml += this.formatLink(org.sameAs, "youtube.com", "Youtube") 
      socialHtml += this.formatLink(org.sameAs, "wikipedia.org", "Wikipedia") 
      socialHtml += this.formatLink(org.sameAs, "instagram.com", "Instagram")
    }
    return socialHtml.slice(0, -2)
  }

  formatLink(sameAs, detectionStr, label) {
    let link = this.linkExtraction(sameAs, detectionStr)
    if (link.length) {
      link = "<a href=\"" + link + "\">" + label + "\</a\> | "
    }
    return link
  }

  linkExtraction(sameAs, detectionStr) {
    let extractId = '' 
    if (typeof sameAs == 'object') {
      let id = ''
      if (sameAs.length) {
        sameAs.forEach(data => {
          if (typeof data == 'object') {
            id = data.id
          } else {
            id = data
          }
          if (id.includes(detectionStr)) {
            extractId = id
          }
        })
      } else {
        if (sameAs.id) {
          if (sameAs.id.includes(detectionStr)) {
            extractId = sameAs.id
          }
        }
      }
    } else if (typeof sameAs == 'string') {
      if (sameAs.includes(detectionStr)) {
        extractId = sameAs
      }
    }
   
    return extractId 
  }


  organizationType(additionalType) {
    return this.generalType(additionalType, "PrimaryActivity")  || "None selected"
  }

  presentationFormat(additionalType) {
    return this.generalType(additionalType, "PresentingFormat")  || "None selected"
  }

  disciplines(additionalType) {
    return this.generalType(additionalType, "Discipline") || "None selected"
  }
  
  generalType(allTypes, detectionStr) {
    let str = ""
    allTypes.forEach(data => {
      if (data.id) {
        if (data.id.includes(detectionStr)) {
          if (data.label && data.label != "empty") {
            str += data.label + ", "
          }
        }
      }
    })
    return str.slice(0, -2)
  }

  links(sameAs) {
    let html_list = "<ul>"
    if (sameAs.length) {
      sameAs.forEach(data => {
        if (data.id) {
          html_list += "<li>" + data.id
        } else {
          html_list += "<li>" + data
        }
      })
      html_list += "</ul>"
    } else {
      html_list += "<li>" + sameAs
    }
    return html_list
  }

  venues(locations) {
    let html_list = "<ul>"
    if (locations.length) {
      locations.forEach(data => {
        if (data.roleName) {
          html_list += "<li>" + data.roleName
          html_list += " : <b>" + data.location.namePref + "</b>"
        }
      })
      html_list += "</ul>"
    } else {
      html_list += "<li>" + locations
    }
    return html_list
  }


  main_attributes(types) {
    let html_list = "<ul>"
    types.forEach(data => {
      if (data.label) {
        html_list += "<li>" + data.label
      }
    })
    html_list += "</ul>"
    return html_list
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
    } else if (mode == "OnlineEventAttendanceMode") {
      modeHtml = "SPECTACLE VIRTUEL"
    }
    return `<span style='color: red;'>${modeHtml}</span>`
  }

  dateFormat(event) {
    return new Date(event.startDate).toLocaleString('fr-FR', {
      dateStyle: 'long',
      timeStyle: 'short',
      hour12: false,
      timeZone: 'EST'
    }) || event.startDateWithoutTime
  }
}


customElements.define('artsdata-org-detail', ArtsdataOrgDetail)