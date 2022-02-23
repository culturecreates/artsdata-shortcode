class ArtsdataOrgDetail extends HTMLElement {

  set org(org) {
    this.innerHTML = `<div>
    <h3> ${  org.namePref || org.nameEn  || org.nameFr }</h3>
    <p> <a href='/member?uri=${ this.artsdataId(org.sameAs)}'> ${ this.artsdataId(org.sameAs)}</a>
    <p> ${ this.officialUrl(org.url[0])  } </p>
    <p> ${ org.address.addressLocality  }, ${ org.address.addressRegion  }, ${ org.address.addressCountry  } </p>
    <p> Main attributes: <br> ${this.main_attributes(org.additionalType)}  </b></p>
    <p> Disciplines: ${this.disciplines(org.additionalType) } </p>
    <p> Presentation Format: ${this.presentationFormat(org.additionalType) } </p>
    <p> Canadian Business Number: ${org.businessNumber} </p>
    <p> Links: <br> ${this.links(org.sameAs)}  </b></p>
    <p> Venues: <br> ${this.venues(org.location)}  </b></p>
    <code> ${ JSON.stringify(org)}</code>
    </div>`
  }
  presentationFormat(additionalType) {
    let str = ""
    additionalType.forEach(data => {
      if (data.id) {
        if (data.id.includes("PresentingFormat")) {
          if (data.label) {
            str += data.label + ", "
          }
        }
      }
    })
    return str.slice(0, -2)
  }
  disciplines(disciplines) {
    let disciplineList = ""
    disciplines.forEach(data => {
      if (data.id) {
        if (data.id.includes("Discipline")) {
          if (data.label) {
            disciplineList += data.label + ", "
          }
        }
      }
    })
    return disciplineList.slice(0, -2)
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

  artsdataId(sameAs) {
    if (typeof sameAs == 'object') {
      let artsdataId = ''
      let id = ''
      if (sameAs.length) {
        sameAs.forEach(data => {
          if (typeof data == 'object') {
            id = data.id
          } else {
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
    } else {
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