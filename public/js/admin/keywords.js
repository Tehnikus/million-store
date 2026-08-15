document.addEventListener('DOMContentLoaded', () => {
  const interface = window.keywordsInterface;
  console.log(interface);
  
  const keywords  = interface.keywords;
  const addGroupBtns = document.querySelectorAll('.addKeywordGroupBtn');
  const groupList     = document.querySelectorAll('.keywordGroups');

  for (const el of interface.keywordGroups) {
    const groupElement = renderKeywordGroupElm(el.id, el.name, interface);
    groupList?.forEach(list => appendKeywordGroup(groupElement, list));
  }

  addGroupBtns?.forEach(btn => {
    btn.addEventListener('click', async e => {
      const groupName = e.target.closest('button').previousElementSibling.value;
      if (!groupName) { return }

      const newGroup = await saveKeywordGroup(groupName);
      if (!newGroup.id) { return }

      const newGroupEl = renderKeywordGroupElm(newGroup.id, groupName, interface);
      groupList?.forEach(list => appendKeywordGroup(newGroupEl, list));

      document.querySelectorAll('[data-search-column="keyword_group_id"], [data-add-row-column="keyword_group_id"], [data-column="keyword_group_id"]').forEach(select => {
        select.add(Object.assign(document.createElement('option'), {value: newGroup.id, textContent: groupName}));
      });
      interface.groupSelect.add(Object.assign(document.createElement('option'), {value: newGroup.id, textContent: groupName}));
    });

    btn.parentElement.querySelector('input[data-add-keyword-group]').addEventListener('keydown', e => {
      if (e.key === "Enter") {
        e.preventDefault();
        btn.dispatchEvent(new Event('click'));
      }
    });
  });

  interface.languageSelect = renderSelect(
    interface.languages.map(l => ({ value: l.id, label: l.name }))
  );

  interface.groupSelect = renderSelect(
    interface.keywordGroups.map(g => ({ value: g.id, label: g.name }))
  );

  const tableElements = document.querySelectorAll('.keywordTable');
  tableElements.forEach(tableElement => {
    keywords.forEach(row => { row.rowType = 'existing'; row.keyword_id = row.id; });
    renderKeywords(interface, keywords, tableElement);
  });
});

// Save keyword group
async function saveKeywordGroup(groupName) {
  return window.pageWire.call('saveKeywordGroup', groupName.slice(0, 100));
}

// // Insert keyword group element
function appendKeywordGroup(el, target) {
  target.insertAdjacentElement('beforeend', el);
}

// Render keyword group element
function renderKeywordGroupElm(id, name, interface) {
  const groupElement = document.createElement('span');
  groupElement.className = 'fi-badge fi-color-gray inline-flex items-center gap-1';
  groupElement.dataset.groupId = id;

  const nameElement = document.createElement('span');
  nameElement.innerText = name;

  const deleteButton = document.createElement('button');
  deleteButton.type = 'button';
  // deleteButton.className = 'fi-icon-btn';
  deleteButton.innerHTML = `<svg class="fi-icon" style="width: 0.75rem; height: 0.75rem" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>`;

  deleteButton.addEventListener('click', async () => {
    await window.pageWire.call('deleteKeywordGroup', id);
    groupElement.remove();
    document.querySelectorAll('[data-search-column="keyword_group_id"], [data-add-row-column="keyword_group_id"], [data-column="keyword_group_id"]').forEach(select => {
      select.querySelector(`option[value="${id}"]`)?.remove();
    });
    interface.groupSelect.querySelector(`option[value="${id}"]`)?.remove();
  });

  groupElement.appendChild(nameElement);
  groupElement.appendChild(deleteButton);

  return groupElement;
}

// function renderKeywordGroupElm(id, name, interface) {
//   const groupElement  = document.createElement('div');
//   const nameElement   = document.createElement('span');
//   const deleteButton  = document.createElement('button');
//   groupElement.classList.add("keywordGroup");
//   nameElement.innerText = name;

//   deleteButton.classList.add("btn", "btn-danger", "btn-xs", "deleteKeywordGroup");
//   deleteButton.innerHTML = `<i class="fa fa-times"></i>`;
//   // Add event listener for remove button
//   deleteButton.addEventListener('click', async () => {
//     await window.pageWire.call('deleteKeywordGroup', id);
//     groupElement.remove();
//     document.querySelectorAll('[data-search-column="keyword_group_id"], [data-add-row-column="keyword_group_id"], [data-column="keyword_group_id"]').forEach(select => {
//       select.querySelector(`option[value="${id}"]`)?.remove();
//     });
//     interface.groupSelect.querySelector(`option[value="${id}"]`)?.remove();
//   });

//   groupElement.appendChild(nameElement);
//   groupElement.appendChild(deleteButton);
//   groupElement.dataset.groupId = id;

//   return groupElement;
// }

// Render keywords table
function renderKeywords(interface, keywords, tableElement) {

    // Table instance
    const keywordTable = new nimbleTable({
      table: tableElement,
      idField:  'keyword_id',
      pagination: {perPage: 200},
      template: (row) => renderRow(interface, row),
      addEventListeners: (table) => {
        table.addEventListener('change', e => {
          // Save rows when any row input changed by user
          if (e.target.closest('[data-id]')) {
            const tr = e.target.closest('[data-id]');
            const newData = {};
            newData.keyword_id = tr.dataset.id;
            newData.rowType = 'updatedRow'
            const inputs = tr.querySelectorAll('input, select');
            inputs.forEach(input => {
              newData[input.dataset.column] = input.value;
            });
            saveKeywords([newData]);
            keywordTable.updateRow(newData.keyword_id, newData, false);
            tr.className = '';
            tr.classList.add('updatedRow');
            tr.querySelector('.rowType').innerText = interface.lang.option_updated;
          }
        })
      },
      onFilterEnd: (filteredMap) => {
        // console.log(filteredMap);
      },
      onRowDelete: async (row) => {
        await window.pageWire.call('deleteKeywords', [row.keyword_id]);
      }
    });

    // Set each loaded row type to 'existing'. Originally row type is not stored in DB, it's just for filtering purpose 
    keywords.forEach(row => {
      row.rowType = 'existing';
    });

    // Render table header element 
    const tableHeaderElement = renderHeader(interface);
    // Append header to table, 
    keywordTable.renderHeader(tableHeaderElement);
    keywordTable.setData(keywords);
  
    // Copy row
    keywordTable.tbody.addEventListener('click', e => {
      if (e.target.closest('[data-copy-row]')) {
        copyRow(keywordTable, e);
      }
      if (e.target.closest('[data-add-to-page]')) {
        addKeywordToPage(keywordTable, interface, e);
      }
    });

    // Prevent form from submit on enter press
    keywordTable.table.addEventListener('keydown', e => {
      if (e.key === "Enter") {
        e.preventDefault();
      }
    });
  
    // Add new row
    tableHeaderElement.querySelector('.addRow').addEventListener('click', e => {
      const newRow = e.target.closest('tr');
      addRow(keywordTable, newRow);
    });

    // Add new row on enter press in add row inputs
    tableHeaderElement.querySelectorAll('input[data-add-row-column][type="text"]').forEach(input => {
      input.addEventListener('keydown', e => {      
        if (e.key === "Enter") {
          e.preventDefault();
          const newRow = e.target.closest('tr');
          addRow(keywordTable, newRow);
        }
      });
    });
  
    // Clear filters
    tableHeaderElement.querySelector('.clearFilters').addEventListener('click', () => {
      keywordTable.resetFilter();
      keywordTable.setData(keywords); // Set data in case find duplicate was used to return to original state
    });
  
    // Find duplicates
    tableHeaderElement.querySelector('.findDuplicates').addEventListener('click', () => {
      const keywordIds = keywordTable.filteredOrder;
      const rows = [];
      keywordIds.forEach(id => {
        const row = keywordTable.getRow(id);
        if (row !== null) {
          rows.push(row);
        }
      });
  
      const duplicates = findDuplicates(rows);
      keywordTable.clearData();
      keywordTable.setData(duplicates);
    });
  
    // Replace string event listener
    tableHeaderElement.querySelectorAll('.replace').forEach(button => {
      button.addEventListener('click', (e) => {
        
        const newRow = e.target.closest('div');
        const newData = {};
        let textReplace;
        newRow.querySelectorAll('input, select').forEach(element => {
          newData[element.dataset.addRowColumn] = element.value || '';
          textReplace = (element.selectedOptions) ? element.selectedOptions[0]?.text : element.value;
        });
        if (!confirm(interface.lang.confirm_replace.replace('{{ text }}', textReplace))) {return}
        updateRow(keywordTable, newData);
      });
    });
  
    // Add to the beginning fo the string event listener
    tableHeaderElement.querySelectorAll('.addToBeginning').forEach(button =>{
      button.addEventListener('click', (e) => {
        const newRow = e.target.closest('div');
        const newData = {};
        let textReplace;
        newRow.querySelectorAll('input').forEach(element => {
          newData[element.dataset.addRowColumn] = element.value || '';
          textReplace = (element.selectedOptions) ? element.selectedOptions[0]?.text : element.value;
        });

        if (!confirm(interface.lang.confirm_add_to_beginning.replace('{{ text }}', textReplace))) {return}

        const items = keywordTable.filteredOrder;
        items.forEach(id => {
          rowVals = keywordTable.rowMap.get(id);
          for (const key in newData) {
            rowVals[key] = String(newData[key]) + String(rowVals[key]);
          }
          rowVals.rowType = 'updatedRow';
          keywordTable.updateRow(id, rowVals, updateElement = true);
        });
      });
    });
  
    // Add to the beginning fo the string event listener
    tableHeaderElement.querySelectorAll('.addToEnd').forEach(button =>{
      button.addEventListener('click', (e) => {
        const newRow = e.target.closest('div');
        const newData = {};
        let textReplace;
        newRow.querySelectorAll('input').forEach(element => {
          newData[element.dataset.addRowColumn] = element.value || '';
          textReplace = (element.selectedOptions) ? element.selectedOptions[0]?.text : element.value;
        });
        if (!confirm(interface.lang.confirm_add_to_end.replace('{{ text }}', textReplace))) {return}
        
        const items = keywordTable.filteredOrder;
        items.forEach(id => {
          rowVals = keywordTable.rowMap.get(id);
          for (const key in newData) {
            rowVals[key] = String(rowVals[key]) + String(newData[key]);
          }
          rowVals.rowType = 'updatedRow';
          keywordTable.updateRow(id, rowVals, updateElement = true);
        });
      });
    });
  
    // Save all keywords
    document.querySelector('.saveAllKeywords').addEventListener('click', async () => {
      const rows = keywordTable.rowMap
      const savedData = [];
      rows.forEach((row) => {
        savedData.push({
          keyword_id:   row.id,
          keyword_text: row.keyword_text,
          keyword_url:  row.keyword_url,
          language_id:  row.language_id,
          store_id:     row.store_id,
        })
      });
      saveKeywords(savedData);
    });
  
    // Import CSV
    tableHeaderElement.querySelector('.importCSV > input').addEventListener('input', (e) => {
      importCSV(e.target, keywordTable);
    });
}

// Render table row
// function renderRow(interface, row) {
//   const tr = document.createElement('tr');
//   const rowTypeOptions  = {updatedRow: interface.lang.option_updated, newRow: interface.lang.option_new, importedRow: interface.lang.option_imported, existing: interface.lang.option_existing};
//   let   rowTypeLabel = '';

//   tr.dataset.id = row.keyword_id || '';

//   if (row.rowType) {
//     tr.classList.add(row.rowType);
//     rowTypeLabel = rowTypeOptions[row.rowType];
//   }

//   const languageSelect  = interface.languageSelect.cloneNode(true);
//   const groupSelect     = interface.groupSelect.cloneNode(true);
//   languageSelect.dataset.column  = 'language_id';
//   groupSelect.dataset.column     = 'keyword_group_id';

//   [...languageSelect.options].forEach(opt => {
//     if (opt.value == row.language_id) {
//       opt.setAttribute('selected', 'selected');
//     }
//   });
//   [...groupSelect.options].forEach(opt => {
//     if (opt.value == row.keyword_group_id) {
//       opt.setAttribute('selected', 'selected');
//     }
//   });

//   tr.innerHTML = `
//     <td><input data-column="keyword_text" value="${row.keyword_text}" class="form-control"></td>
//     <td><input data-column="keyword_url"  value="${row.keyword_url}"  class="form-control"></td>
//     <td>${languageSelect.outerHTML}</td>
//     <td>${groupSelect.outerHTML}</td>
//     <td class="text-center rowType">${rowTypeLabel}</td>
//     <td class="text-center">
//       <div class="btn-group">
//         <button type="button" class="btn btn-success" data-add-to-page    title="${interface.lang.button_add_to_page}"><i class="fa fa-plus-circle"></i></button>
//         <button type="button" class="btn btn-default" data-copy-row=""    title="${interface.lang.button_copy}"><i class="fa fa-copy"></i></button>
//         <button type="button" class="btn btn-danger"  data-remove-row=""  title="${interface.lang.button_delete}"><i class="fa fa-times"></i></button>
//       </div>
//     </td>
//   `;
  
//   return tr;
// }

function renderRow(interface, row) {
  const tr = document.createElement('tr');
  tr.className = 'fi-ta-row';
  const rowTypeOptions = {updatedRow: interface.lang.option_updated, newRow: interface.lang.option_new, importedRow: interface.lang.option_imported, existing: interface.lang.option_existing};
  let rowTypeLabel = '';

  tr.dataset.id = row.keyword_id || '';

  if (row.rowType) {
    tr.classList.add(row.rowType);
    rowTypeLabel = rowTypeOptions[row.rowType];
  }

  const languageSelect = interface.languageSelect.cloneNode(true);
  const groupSelect    = interface.groupSelect.cloneNode(true);
  languageSelect.dataset.column = 'language_id';
  groupSelect.dataset.column    = 'keyword_group_id';

  [...languageSelect.options].forEach(opt => {
    if (opt.value == row.language_id) opt.setAttribute('selected', 'selected');
  });
  [...groupSelect.options].forEach(opt => {
    if (opt.value == row.keyword_group_id) opt.setAttribute('selected', 'selected');
  });

  tr.innerHTML = `
    <td class="fi-ta-cell"><input data-column="keyword_text" value="${row.keyword_text}" class="fi-input block w-full"></td>
    <td class="fi-ta-cell"><input data-column="keyword_url"  value="${row.keyword_url}"  class="fi-input block w-full"></td>
    <td class="fi-ta-cell">${languageSelect.outerHTML}</td>
    <td class="fi-ta-cell">${groupSelect.outerHTML}</td>
    <td class="fi-ta-cell text-center rowType">${rowTypeLabel}</td>
    <td class="fi-ta-cell text-center">
      <div class="flex gap-1 justify-center">
        <button type="button" class="fi-btn fi-btn-size-sm fi-background-success " data-add-to-page title="${interface.lang.button_add_to_page}">
          <span><svg class="fi-icon fi-size-sm" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg></span>
        </button>
        <button type="button" class="fi-btn fi-btn-size-sm fi-background-gray " data-copy-row="" title="${interface.lang.button_copy}">
          <span><svg class="fi-icon fi-size-sm" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/></svg></span>
        </button>
        <button type="button" class="fi-btn fi-btn-size-sm fi-background-danger " data-remove-row="" title="${interface.lang.button_delete}">
          <span><svg class="fi-icon fi-size-sm" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg></span>
        </button>
      </div>
    </td>
  `;

  return tr;
}

// Render table haedaer
function renderHeader(interface) {
  const thead           = document.createElement('thead');

  // Create filter selects
  const languageSelect  = interface.languageSelect.cloneNode(true);
  const groupSelect     = interface.groupSelect.cloneNode(true);
  // Add empty values to filter selects
  languageSelect.add(Object.assign(document.createElement('option'), {value: '', textContent: interface.lang.column_language}), languageSelect.options[0]);
  groupSelect.add(Object.assign(document.createElement('option'), {value: '', textContent: interface.lang.column_group}), groupSelect.options[0]);

  // Set dataset
  languageSelect.dataset.searchColumn = 'language_id';
  groupSelect.dataset.searchColumn    = 'keyword_group_id';

  // Row type select options 
  const rowTypeOptions  = [{value: '', label: interface.lang.option_all_types}, {value: 'existing', label: interface.lang.option_existing}, {value: 'updatedRow', label: interface.lang.option_updated}, {value: 'newRow', label: interface.lang.option_new}, {value: 'importedRow', label: interface.lang.option_imported}];
  const filterRowTypeSelect  = renderSelect(rowTypeOptions,  {searchColumn: 'rowType'});

  // Render addRow selects
  const addRowLangSelect  = interface.languageSelect.cloneNode(true);
  const addRowGroupSelect = interface.groupSelect.cloneNode(true);
  addRowLangSelect.dataset.addRowColumn   = 'language_id';
  addRowGroupSelect.dataset.addRowColumn  = 'keyword_group_id';
  
  thead.innerHTML = `
    <tr>
      <th class="fi-ta-header-cell fi-align-center">
        <div class="fi-input-wrp">
          <input type="text" class="fi-input fi-input-wrp-content-ctn" data-add-row-column="keyword_text" placeholder="${interface.lang.column_add_keyword}/${interface.lang.column_edit_keyword} ${interface.lang.column_seo_keyword}">
          <button type="button" title="${interface.lang.button_add_to_beginning}" class="  fi-input-wrp-suffix fi-btn-size-sm fi-color-gray addToBeginning"><i class="fa fa-fast-backward"></i></button>
          <button type="button" title="${interface.lang.button_add_to_end}" class="  fi-input-wrp-suffix fi-btn-size-sm fi-color-gray addToEnd"><i class="fa fa-fast-forward"></i></button>
          <button type="button" title="${interface.lang.button_replace}" class=" fi-input-wrp-suffix fi-btn-size-sm fi-color-gray replace"><i class="fa fa-random"></i></button>
        </div>
      </th>
      <th class="fi-ta-header-cell fi-align-center">
        <div class="fi-input-wrp">
          <input type="text" class="fi-input" data-add-row-column="keyword_url" placeholder="${interface.lang.column_url}">
          <button type="button" title="${interface.lang.button_add_to_beginning}" class="fi-btn addToBeginning"><i class="fa fa-fast-backward"></i></button>
          <button type="button" title="${interface.lang.button_add_to_end}" class="fi-btn addToEnd"><i class="fa fa-fast-forward"></i></button>
          <button type="button" title="${interface.lang.button_replace}" class="fi-btn btn-warning replace"><i class="fa fa-random"></i></button>
        </div>
      </th>
      <th class="fi-ta-header-cell fi-align-center">
        <div class="fi-input-wrp">
         ${addRowLangSelect.outerHTML}<button type="button" title="${interface.lang.button_replace}" class="fi-btn btn-warning replace"><i class="fa fa-random"></i></button>
        </div>
      </th>
      <th class="text-center">
        <div class="fi-input-wrp">
         ${addRowGroupSelect.outerHTML}<button type="button" title="${interface.lang.button_replace}" class="fi-btn btn-warning replace"><i class="fa fa-random"></i></button>
        </div>
      </th>
      <th class="text-center"></th>
      <th class="text-center">
        <div class="fi-input-wrp-content-ctn">
          <button type="button" class="fi-btn btn-success addRow" title="${interface.lang.button_add_row}"><i class="fa fa-plus-circle"></i></button>
          <label class="btn btn-primary importCSV" title="${interface.lang.button_import}">
            <i class="fa fa-cloud-upload"></i>&nbsp;
            <input type="file" accept=".csv" style="display: none;" />
          </label>
          <button type="button" class="fi-btn btn-success saveAllKeywords" title="${interface.lang.button_save_all}"><i class="fa fa-save"></i></button>
        </div>
      </th>
    </tr>
    <tr>
      <th style="width: auto"   class="fi-ta-header-cell"><div class="fi-input-wrp"><input type="text" class="fi-input" data-search-column="keyword_text" placeholder="${interface.lang.text_search} ${interface.lang.column_seo_keyword}"></div></th>
      <th style="width: auto"   class="fi-ta-header-cell"><div class="fi-input-wrp"><input type="text" class="fi-input" data-search-column="keyword_url" placeholder="${interface.lang.text_search} ${interface.lang.column_url}"></div></th>
      <th style="width: 180px"  class="fi-ta-header-cell"><div class="fi-input-wrp">${languageSelect.outerHTML}</div></th>
      <th style="width: 180px"  class="fi-ta-header-cell"><div class="fi-input-wrp">${groupSelect.outerHTML}</div></th>
      <th style="width: 130px"  class="fi-ta-header-cell"><div class="fi-input-wrp">${filterRowTypeSelect.outerHTML}</div></th>
      <th style="width: 130px"  class="fi-ta-header-cell">
        <div class="fi-input-wrp-content-ctn">
          <button type="button" class="fi-btn findDuplicates" title="${interface.lang.button_find_duplicates}"><i class="fa fa-search-plus"></i></button>
          <button type="button" class="fi-btn clearFilters" title="${interface.lang.button_clear_filters}"><i class="fa fa-times"></i></button>
        </div>
      </th>
    </tr>
  `;

  return thead;
}

// Render select from options list 
function renderSelect(options, datasetAttr) {
  const select = document.createElement('select');
  select.className = 'fi-select-input fi-input block w-full';

  if (datasetAttr) {
    Object.entries(datasetAttr).forEach(([k, v]) => {
      select.dataset[k] = v;
    });
  }

  options.forEach(opt => {
    const option = document.createElement('option');
    option.value = opt.value;
    option.textContent = opt.label;
    select.appendChild(option);
  });

  return select;
}

// Add row from form
function addRow(keywordTable, newRow) {
  const newData = {};
  let returnFlag = false;
  newRow.querySelectorAll('input[type="text"], select').forEach(element => {
    if (element.tagName === 'INPUT' && element.value === '') {
      element.classList.add('alert-danger');
      returnFlag = true;
    } else {
      element.classList.remove('alert-danger');
    }
    newData[element.dataset.addRowColumn] = element.value || '';
  });
  if (returnFlag) {
    newRow.querySelector('input.alert-danger')?.focus();
    return;
  }
  newData.rowType = 'newRow';
  const table = keywordTable.setData([newData], true);
  keywordTable.setPage(keywordTable.getTotalPages());
  table.lastChild.scrollIntoView({block: "nearest", inline: "nearest"});
  saveKeywords([newData]);
}

// copy existing row
function copyRow(keywordTable, e) {
  const id = Number(e.target.closest('[data-id]').dataset.id);
  const rowData = {...keywordTable.rowMap.get(id)}; // Copy row instead of reusing it, because in JavaScript objects are reference types (assignments copy references, not the actual object)
  delete rowData.keyword_id; // Delete values that are treated as row identifier. If not deleted, Map() will skip duplicate ids
  delete rowData.id; // Delete values that are treated as row identifier. If not deleted, Map() will skip duplicate ids
  rowData.rowType = 'newRow';
  keywordTable.setData([rowData]);
  saveKeywords([rowData]);
}

function addKeywordToPage(keywordTable, interface, e) {
  const id = Number(e.target.closest('[data-id]').dataset.id);
  const rowData = {...keywordTable.rowMap.get(id)};
  const pageKeywords = document.querySelector(`${document.querySelector('#keywordsTabLanguage li.active a').hash} tbody`);
  const index = pageKeywords.childElementCount;
  // Get language id from current active tab. This way keyword from any language can be added to desired page language
  const languageId = document.querySelector('#keywordsTabLanguage li.active a').dataset.tabLanguageId;
  let newKeyword = `
    <tr data-saved-keyword-id="">
      <td>
        <input 
          name="${name_prefix}[${languageId}][seo_keywords][${index}][keyword]"
          value="${rowData.keyword_text}"
          class="form-control"
        />
      </td>
      <td>
        <input 
          name="${name_prefix}[${languageId}][seo_keywords][${index}][url]"
          value="${rowData.keyword_url}"
          class="form-control"
        />
      </td>
      <td style="text-align: center">
        <div class="btn-group">
          <button type="button" onclick="moveRow(this, 'up')" class="fi-btn" title="${interface.lang.button_move_up}"><i class="fa fa-arrow-up"></i></button>
          <button type="button" onclick="moveRow(this, 'down')" class="fi-btn" title="${interface.lang.button_move_down}"><i class="fa fa-arrow-down"></i></button>
          <button type="button" onclick="this.closest('tr').remove()" class="btn btn-danger" title="${interface.lang.button_remove_keyword}"><i class="fa fa-times"></i></button>
        </div>
      </td>
    </tr>
  `;
  pageKeywords.insertAdjacentHTML('beforeend', newKeyword);
}

// Save keyword to DB
function saveKeywords(data) {
  return window.pageWire.call('saveKeywords', data)
    .then(r => console.log(r));
}

// Replace value in rows
function updateRow(keywordTable, newData) {
  const items = keywordTable.filteredOrder;
  items.forEach(id => {
    rowVals = keywordTable.rowMap.get(id);
    for (const key in newData) {
      rowVals[key] = String(newData[key]);
    }
    rowVals.rowType = 'updatedRow';
    keywordTable.updateRow(id, rowVals, updateElement = true);
  });
}

// Import CSV
function importCSV(input, keywordTable) {
  // File
  const file = input.files[0];

  if (file) {
    const reader = new FileReader();
    // Read file
    reader.onload = (e) => {
      // File read results
      const contents = e.target.result;
      // Parse as CSV data
      const parsedData = parseCSV(contents, detectDelimiter(contents));
      parsedData.forEach(row => {
        row.rowType = 'importedRow';
      });
      keywordTable.setData(parsedData);
      saveKeywords(parsedData);

    };
    reader.readAsText(file);
  }

  function detectDelimiter(data) {
    const firstLine = data.split(/\r?\n/)[0];
  
    const commaCount = (firstLine.match(/,/g) || []).length;
    const semicolonCount = (firstLine.match(/;/g) || []).length;
  
    return semicolonCount > commaCount ? ';' : ',';
  }

  function normalizeHeader(header) {
    return header
      .trim()
      .toLowerCase()
      .replace(/\s+/g, '_'); // "Keyword Text" => "keyword_text"
  }

  function parseCSV(data, delimiter = ',') {
    const allowedColumns = [
      'keyword_id',
      'keyword_text',
      'keyword_url',
      'keyword_group_id',
      'language_id',
      'store_id'
    ];
    data = data.replace(/^\uFEFF/, ''); // Replace Excel-specific character
    const rows = [];
    let row = [];
    let value = '';
    let insideQuotes = false;

    for (let i = 0; i < data.length; i++) {
      const char = data[i];
      const nextChar = data[i + 1];

      if (char === '"') {
        if (insideQuotes && nextChar === '"') {
          value += '"';
          i++; 
        } else {
          insideQuotes = !insideQuotes;
        }
      } else if (char === delimiter && !insideQuotes) {
    
        row.push(value.trim());
        value = '';
      } else if ((char === '\n' || char === '\r') && !insideQuotes) {
        if (value !== '' || row.length > 0) {
          row.push(value.trim());
          rows.push(row);
          row = [];
          value = '';
        }
    
        if (char === '\r' && nextChar === '\n') {
          i++;
        }
      } else {
        value += char;
      }
    }

    if (value !== '' || row.length > 0) {
      row.push(value.trim());
      rows.push(row);
    }
    
    const cleanRows = rows.filter(r => r.some(cell => cell.trim() !== '')); // Remove empty rows
  
    const headers = (cleanRows.shift() || []).map(h => normalizeHeader(h)); // Normalize headers "Keyword Text" => "keyword_text"
  
    return cleanRows.map(line => {
      const obj = {};
    
      allowedColumns.forEach(col => {
        obj[col] = '';
      });
    
      headers.forEach((header, i) => {
        if (!allowedColumns.includes(header)) return;
    
        let val = (line[i] ?? '').trim();
    
        if (['keyword_group_id', 'language_id', 'store_id'].includes(header)) {
          val = parseInt(val) || 1;
        }
        if (['store_id'].includes(header)) {
          val = parseInt(val) || 0;
        }
    
        obj[header] = val;
      });
    
      return obj;
    });
  }

  input.value = ''; // Clear input value. This fixes bug when same file selected twice and parser didn't do anything
}

// Find duplicate keywords
function findDuplicates(data) {
  const map = new Map();

  data.forEach(item => {
    const key = item.keyword_text.trim().toLowerCase();
    map.set(key, (map.get(key) || 0) + 1);
  });

  return data.filter(item => {
    const key = item.keyword_text.trim().toLowerCase();
    return map.get(key) > 1;
  });
}

function moveRow(button, direction) {
  const row = button.closest('tr');
  const parent = row.parentNode;

  if (direction === 'up') {
    const prevRow = row.previousElementSibling;
    if (prevRow) {
      // insertBefore moves the row before the previous one
      parent.insertBefore(row, prevRow);
    }
  } else if (direction === 'down') {
    const nextRow = row.nextElementSibling;
    if (nextRow) {
      // To move down, insert after the next row
      // We achieve "after" by inserting before the sibling of the next row
      parent.insertBefore(row, nextRow.nextElementSibling);
    }
  }
}