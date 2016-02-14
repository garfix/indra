# Target

The goal of this system is be a data store with these main features:
 
 - easy to use ORM with types, attributes, and objects
 - uses and integrates with an existing relational database 
 - class generation for types and attributes
 - one attribute may be part of more than one type
 - each set of changes forms a commit in a branch
 - branches may be created and merged
 - an application may use the data of a previous point in history (any previous commit)
 - data is stored both as views (full tables) and as diffs (commits)
 - a commit may be reverted (made undone) if no later commits depend on it
 - the commit-history of an object, a type, and a user can be inspected     

## Concepts and tables

### Branch

A thread of commits, of which only the last one is saved.

- branch-id (string) // indra-id
- commit-index (int)
- mother-branch-id (string) // indra-id
- mother-commit-index (int)

The mother-commit-id is the commit from which this branch has sprung.

### Commit

A commit is a set of changes committed to the database.

 - branch-id (string) // indra-id
 - commit-index (int) 
 - reason
 - username (string)
 - datetime
 - merge-branch-id // indra-id
 - merge-commit-index (int)

The merge-commit-id is only filled when a the commit is created from the merge of another branch.
  It is used to when the same branche is merged again. All commits before this merge-commit-id are not merged again. 
  
### Commit per type

An index into the commits, to find all objects of a given type that have changed in some span. 

 - branch-id
 - branch-commit-index
 - type-id
 - diff (longtext) 
 
The diff is an encoded structure of multiple changes. Examples:
 
 - Change of attribute:
 
ADD ATTRIBUTE last-modified, 1/1/2015; REMOVE ATTRIBUTE last-modified, 1/1/2014
  
 - Change of type:
  
ADD COLUMN color

### View

A view is a flat representation of all objects of a type.

### View table

Table name: view-{view-id}

 - {type-specific-field}*

### Snapshot

A snapshot is a view that is linked to a commit

 - view-id
 - branch-id
 - commit-index

### Branch view

A view that is updated each time the branch gets a new commit.
If a branch view matches a snapshot, that's incidental. Snapshot and branches are treated separately.

 - view-id
 - branch-id

## Processes

### A new commit

 - Create a commit
 - Create commit types
 - Change the head-commit-id of the branch to the new commit
 - Is the branch-view used by other branches as well?
    - Yes: Create a clone of the active view, update this clone and update the branch-view
    - No: Update the view-table of the branch view with the new commit

### A new branch

 - Create a branch from the latest commit of the mother branch
 - Create branch-view objects for all types for this branch (note: no need to copy complete views)

### Merge

A merge from branch A to branch B goes as follows:

 - the common ancestor commit is found
    - find the common mother-branch and the oldest index of this branch that needs merging
 - all commits in between this common commit and the last commit of A are applied in a single commit to branch B
 - commits from A before a merge-commit-id in B are discarded
 
### Undo a commit

An undo of a commit is qua data just a new commit.

### Checkout of a commit (other than HEAD)

When a specific commit is checked out by a request, in fact a single snapshot is requested.

 - Does this snapshot exist?
    - No: 
        - created the view-table and the snapshot
        - apply all commit diffs that affect the type up until the requested commit are applied to the snapshot in reverse, 
          including table changes.
          
          For example: if the requested commit C is part of branch B, and looks like this: 103.88, then
          all commits 103.89, 103.90, 103.91, ... whose type-id matches that of the requested type
          are collected and applied to the snapshot
          
    - Yes: no action
    
Use the snapshot.

Snapshots may be removed with a single API call.

### The type changes

 - Classes must be recreated
 - A special type change commit is created
